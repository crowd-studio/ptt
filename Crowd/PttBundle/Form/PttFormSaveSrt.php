<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PttFormSaveSrt extends PttFormSave
{
    public function value()
    {
        $value = [];
        $files = $this->_files();

        if ($this->languageCode) {
            $file = (isset($files["Trans"][$this->languageCode][$this->field->name])) ? $files["Trans"][$this->languageCode][$this->field->name] : false;
        } else {
            $file = (isset($files[$this->field->name])) ? $files[$this->field->name] : false;
        }
        if ($file) {
            // CREAR
            $entity  = $this->classNameForRelatedEntity();
            $lines   = file('test.srt');

            $subs    = [];
            $state   = SRT_STATE_SUBNUMBER;
            $subText = '';
            $subTime = '';
            $id = 0;

            foreach ($lines as $line) {
                switch ($state) {
                    case 0:
                        $state  = 1;
                        break;

                    case 1:
                        $subTime = trim($line);
                        $state   = 2;
                        break;

                    case 2:
                        if (trim($line) == '') {
                            $time = explode(' --> ', $subTime)[0];
                            $time = explode(',', $time)[0];
                            list($horas, $minutos, $segundos) = explode(':', $time);
                            $hora_en_segundos = ($horas * 3600) + ($minutos * 60) + $segundos;

                            $sub = new $entity();
                            $sub->setText($subText);
                            $sub->setSecond($hora_en_segundos);
                            $sub->setVideo($this->entityInfo->getEntity());
                            $sub->set_Order($id);
                            $sub->set_Model($this->entityInfo->getEntityName());
                            $sub->setUpdateObjectValues();

                            $subText     = '';
                            $state       = 0;
                            $id++;
                            $subs[]      = $sub;
                        } else {
                            $subText .= $line;
                        }
                        break;
                }
            }
        } else {
            $value = $this->entityInfo->get($this->field->name, $this->languageCode);
            if ($this->languageCode) {
                if (isset($this->sentData[$this->languageCode][$this->field->name . '-delete']) && $this->sentData[$this->languageCode][$this->field->name . '-delete'] != '0') {
                    $value = [];
                }
            } else {
                if (isset($this->sentData[$this->field->name . '-delete']) && $this->sentData[$this->field->name . '-delete'] != '0') {
                    $value = [];
                }
            }
        }

        return $value;
    }

    public function classNameForRelatedEntity()
    {
        $classNameArr = explode('\\', $this->entityInfo->getClassName());
        array_pop($classNameArr);
        return implode('\\', $classNameArr) . '\\' . $this->field->options['entity'];
    }

    private function _files()
    {
        if (strpos($this->entityInfo->getFormName(), '[') !== false) {
            $cleanName = str_replace(']', '', $this->entityInfo->getFormName());
            $cleanNameArr = explode('[', $cleanName);
            $i = 0;
            $files = array();
            foreach ($cleanNameArr as $key) {
                if ($i == 0) {
                    $files = $this->request->files->get($key);
                } else {
                    if (isset($files[$key])) {
                        $files = $files[$key];
                    }
                }
                $i++;
            }
            return $files;
        } else {
            return $this->request->files->get($this->entityInfo->getEntityName());
        }
    }
}
