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

class PttMediaController extends Controller
{
    private $pttServices;

    /**
     * @Route("/ptt/media/upload/", name="upload");
     * @Template()
     */
    public function uploadAction(Request $request)
    {
        if ($request->files->get('files') !== null) {
            $uploadUrl = PttUtil::pttConfiguration('images');
            $pttInfo = PttUtil::pttConfiguration('s3');
            $uploadToS3 = (isset($pttInfo['force']) && $pttInfo['force']);

            $width = ($request->get('width', false)) ? $request->get('width') : 0;
            $height = ($request->get('height', false)) ? $request->get('height') : 0;

            $field = [
                'name' => 'file',
                'type' => 'file',
                'options' => [
                    'type' => 'image',
                    'sizes' => [['w' => $width, 'h' => $height]]
                ]
            ];

            if ($uploadToS3) {
                $field['options']['s3'] = true;
            }

            $files = $request->files->get('files');

            $file = $files[0];
            $filename = PttUploadFile::upload($file, $field);
            $url = ($uploadToS3) ? $pttInfo['prodUrl'] : $uploadUrl;

            $data = [
                'filename' => $filename,
                'resized' => $url . $width . '-' . $height . '-' . $filename
            ];

            return new JsonResponse($data);
        } else {
            $originalNameArray = explode('.', $_FILES['file']['name']);
            $extension = end($originalNameArray);
            $prefix = (PttUtil::pttConfiguration('prefix') != '') ? PttUtil::pttConfiguration('prefix') : '';
            $shortName = '/tmp/' . PttUtil::token(100) . '.' . $extension;
            $name = __DIR__ . '/../../../../../../web' . $shortName;
            copy($_FILES['file']['tmp_name'], $name);

            $shortName = $prefix . $shortName;
            return new JsonResponse(["file" => $shortName, "path" => $name]);
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
            $data = ['results' => $this->_entities($field, $request->get('query'))];
        }
        return new JsonResponse($data);
    }

    private function _entities($field, $query)
    {
        $sortBy = (isset($field['options']['sortBy']) && is_array($field['options']['sortBy'])) ? $field['options']['sortBy'] : ['id' => 'asc'];
        $filterBy = (isset($field['options']['filterBy']) && is_array($field['options']['filterBy'])) ? $field['options']['filterBy'] : [];
        $search = $field['options']['searchfield'];

        $params = [];

        if ($filterBy) {
            $where = [];
            foreach ($filters as $key => $filter) {
                $keyArr = explode('-', $key);
                $where[] = ['column' => array_pop($keyArr), 'operator' => 'LIKE', 'value' => '%'.$filter.'%'];
            }

            $params['where'] = [['and' => $where]];
        }

        $where = [['column' => $search, 'operator' => 'LIKE', 'value' => '%'.$query.'%']];
        foreach ($filterBy as $key => $value) {
            $where[] = ['column' => $key, 'operator' => '=', 'value' => $value];
        }
        $params['where'] = [['and' => $where]];

        $params['order'] = [];
        foreach ($sortBy as $key => $value) {
            $params['order'][] = ['order' => $key, 'orderDir' => $value];
        }

        $result = $this->getPttServices()->get($field['options']['entity'], $params);

        $textArr = [];
        $method = 'get' . ucfirst($search);
        foreach ($result as $value) {
            if (method_exists($value, $method)) {
                $textArr[] = [
                  'id' => $value->getId(),
                  'text' => $value->$method()
              ];
            }
        }

        return $textArr;
    }

    private function _entity($field, $id)
    {
        $result = $this->getPttServices()->getOne($field['options']['entity'], $id);

        if ($result) {
            $text = '';
            foreach ($search as $key => $field) {
                $method = 'get' . ucfirst($key);
                if (method_exists($result, $method)) {
                    $text .= ', ' . $result->$method();
                }
            }

            return [
                'id' => $result->getId(),
                'text' => $text
            ];
        } else {
            return false;
        }
    }

    protected function getPttServices()
    {
        if (!$this->pttServices) {
            $this->pttServices = $this->get('pttservices');
        }
        return $this->pttServices;
    }
}
