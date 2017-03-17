<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Crowd\PttBundle\Util\PttUtil;

class PttFields
{
    public $static = array();
    public $block = false;
    public $trans = false;
    public $errorMessage;
    public $successMessage;
    public $table;
    private $pttTrans;

    public function __construct($filePath, $entity, $entityName, $formName, $pttTrans)
    {
        $this->pttTrans = $pttTrans;
        try {
            $yaml = new Parser();
            $fields = $yaml->parse(file_get_contents($filePath));

            $this->_parse($fields, $entityName, $formName);

            if (!isset($fields['block'])) {
                $this->block = ["title" => ""];
            }
            $this->_addAdditionFields($formName);
        } catch (ParseException $e) {
            throw new \Exception('Unable to parse the ' . $entityName . '.yml file');
        }
    }

    public function addField($formName, $field)
    {
        $pttField = new PttField($field, $formName);
        $this->static[] = $pttField;
    }

    private function _parse($fields, $entityName, $formName, $block = 0)
    {
        if (isset($fields['static'])) {
            if ($fields['static']) {
                $staticFields = [];
                foreach ($fields['static'] as $field) {
                    $pttField = new PttField($field, $formName);
                    $staticFields[] = $pttField;
                }
                $this->static[] = $staticFields;
            } else {
                $this->static[] = false;
            }
        }

        if (isset($fields['trans'])) {
            if (!isset($this->trans)) {
                $this->trans = [];
            }
            $transFields = [];
            if ($fields['trans']) {
                foreach ($fields['trans'] as $field) {
                    $pttField = new PttField($field, $formName, true);
                    $transFields[] = $pttField;
                }
                $this->trans[] = $transFields;
            } else {
                $this->trans[] = false;
            }
        }

        $this->errorMessage = (isset($fields['errorMessage'])) ? $fields['errorMessage'] : $this->errorMessage = $this->pttTrans->trans('validation_errors_were_found');
        $this->successMessage = (isset($fields['successMessage'])) ? $fields['successMessage'] : $this->pttTrans->trans('content_was_saved');
        $this->table  = (isset($fields['table'])) ? $fields['table'] : $entityName;

        if (isset($fields['block'])) {
            $this->block = [];
            $index = 0;
            foreach ($fields['block'] as $block) {
                $this->block[$index] = $block['title'];
                $this->_parse($block, $entityName, $formName, $index);
                $index++;
            }
        }
    }

    private function _addAdditionFields($formName)
    {
        $field = [
            'name' => 'id',
            'type' => 'hidden',
            'options' => ['label' => 'id']
        ];
        $pttField = new PttField($field, $formName);
        $this->static[0][] = $pttField;
    }
}
