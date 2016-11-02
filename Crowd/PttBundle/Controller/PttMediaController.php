<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Form\PttUploadFile;
use Crowd\PttBundle\Form\PttField;

class PttMediaController extends Controller
{
    /**
     * @Route("/ptt/media/upload/", name="upload");
     * @Template()
     */
    public function uploadAction(Request $request)
    {
        if($request->files->get('files') !== null) {
            $uploadUrl = PttUtil::pttConfiguration('images');
            $pttInfo = PttUtil::pttConfiguration('s3');
            $uploadToS3 = (isset($pttInfo['force']) && $pttInfo['force']) ? true : false;

            if ($request->get('canvas') == 'yes') {

                $sizes = $request->get('sizes', array(array('w' => 0, 'h' => 0)));

                $width = $sizes[0]['w'];
                $height = $sizes[0]['h'];

                $filename = PttUploadFile::uploadCanvas($request->get('imgBase64'), $sizes, $uploadToS3);

                $url = (isset($pttInfo['force']) && $pttInfo['force']) ? $pttInfo['prodUrl'] . $pttInfo['dir'] . '/' : $uploadUrl;

                $data = array(
                    'filename' => $filename,
                    'resized' => $url . $width . '-' . $height . '-' . $filename
                    );

            } else {

                $width = ($request->get('width', false)) ? $request->get('width') : 0;
                $height = ($request->get('height', false)) ? $request->get('height') : 0;

                $fieldData = array(
                    'name' => 'file',
                    'type' => 'file',
                    'options' => array(
                        'type' => 'image',
                        'sizes' => array(
                            array(
                                'w' => $width,
                                'h' => $height
                                )
                            )
                        )
                    );

                if ($uploadToS3) {
                    $fieldData['options']['s3'] = true;
                }

                $field = new PttField($fieldData, 'upload-file');

                $files = $request->files->get('files');

                $file = $files[0];
                $filename = PttUploadFile::upload($file, $field);
                $url = (isset($pttInfo['force']) && $pttInfo['force']) ? $pttInfo['prodUrl'] : $uploadUrl;

                $data = array(
                    'filename' => $filename,
                    'resized' => $url . $width . '-' . $height . '-' . $filename
                    );
            }
            return new JsonResponse($data);
        } else {
            $originalNameArray = explode('.', $_FILES['file']['name']);
            $extension = end($originalNameArray);
            $prefix = (PttUtil::pttConfiguration('prefix') != '') ? PttUtil::pttConfiguration('prefix') : '';
            $shortName = '/tmp/' . PttUtil::token(100) . '.' . $extension;
            $name = substr(__DIR__ . '/../../../../../../', 0, -4) . $shortName;
            copy($_FILES['file']['tmp_name'], $name);
            $shortName = $prefix . $shortName;
            return new JsonResponse(array("file" => $shortName, "path" => $name));
        }


    }

    /**
     * @Route("/ptt/media/autocomplete/", name="autocomplete");
     * @Template()
     */
    public function autocompleteAction(Request $request)
    {
        $field = $request->get('field');
        if ($request->get('type') == 'init') {
            $data = $this->_entity($field, $request->get('id'));
        } else {
            $data = array('results' => $this->_entities($field, $request->get('query')));
        }
        return new JsonResponse($data);
    }

    private function _entities($field, $query)
    {
        $em = $this->get('doctrine')->getManager();
        $sortBy = (isset($field['options']['sortBy']) && is_array($field['options']['sortBy'])) ? $field['options']['sortBy'] : array('id' => 'asc');
        $filterBy = (isset($field['options']['filterBy']) && is_array($field['options']['filterBy'])) ? $field['options']['filterBy'] : array();

        $search = $field['options']['searchfield'];

        $sql = 'select e.id, e.'.$search.' text from ' . $field['options']['entity'] . ' e where ';
        $wheres = array();
        foreach ($filterBy as $key => $value) {
            $wheres[] = 'e.' . $key . ' = :' . $key;
        }

        $wheres[] = 'e.' . $search . ' like :search';
        $sql .= implode(' and ', $wheres);

        if (count($sortBy)) {
            $sql .= ' order by ';
            $orders = array();
            foreach ($sortBy as $key => $value) {
                $orders[] = 'e.' . $key . ' ' . $value;
            }
            $sql .= implode(', ', $orders);
        }

        $stmt = $em->getConnection()->prepare($sql);
        if (count($filterBy)) {
            foreach ($filterBy as $key => $value) {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->bindValue('search', '%' . $query . '%');
        $stmt->execute();
        $results = $stmt->fetchAll();

        return $results;
    }

    private function _entity($field, $id)
    {
        $em = $this->get('doctrine')->getManager();

        $search = array_keys($field['options']['search']);

        $sql = 'select e.id, concat(' . implode(', " ",', $search) . ') text from ' . $field['options']['entity'] . ' e where e.id = :id';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if (count($results)) {
            return $results[0];
        } else {
            return false;
        }
    }
}