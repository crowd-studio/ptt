<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PttFormSaveFile extends PttFormSave
{
    public function value()
    {
        $files = $this->_files();

        if ($this->languageCode) {
             $file = (isset($files["Trans"][$this->languageCode][$this->field->name])) ? $files["Trans"][$this->languageCode][$this->field->name] : false;
        } else {
            $file = (isset($files[$this->field->name])) ? $files[$this->field->name] : false;
        }
        if ($file) {
            $value = PttUploadFile::upload($file, $this->field);
        } else {
            $sentData = $this->request->get($this->entityInfo->getFormName());
            $value = $this->entityInfo->get($this->field->name, $this->languageCode);
            if ($this->languageCode) {
                if (isset($sentData[$this->languageCode][$this->field->name . '-delete']) && $sentData[$this->languageCode][$this->field->name . '-delete'] == 1) {
                    $value = '';
                }
                if (isset($sentData[$this->languageCode][$this->field->name . '-webcam']) && $sentData[$this->languageCode][$this->field->name . '-webcam'] != '') {
                    $value = $sentData[$this->languageCode][$this->field->name . '-webcam'];
                }
            } else {
                if (isset($sentData[$this->field->name . '-delete']) && $sentData[$this->field->name . '-delete'] == 1) {
                    $value = '';
                }
                if (isset($sentData[$this->field->name . '-webcam']) && $sentData[$this->field->name . '-webcam'] != '') {
                    $value = $sentData[$this->field->name . '-webcam'];
                }
            }
        }
        
        if ($this->field->options['type'] == 'gallery') {
            if ($this->languageCode) {
                $path = (isset($this->sentData[$this->languageCode][$this->field->name])) ? $this->sentData[$this->languageCode][$this->field->name] : false;
            } else {
                $path = (isset($this->sentData[$this->field->name])) ? $this->sentData[$this->field->name] : false;
            } 
            if($path){
                $nameArray = explode('/', $path);
                $originalName = end($nameArray);

                $uploadFile = new UploadedFile($path, $originalName, mime_content_type($path), filesize($path));
                $value = PttUploadFile::upload($uploadFile, $this->field);    
            }
        }

        if ($value == null) {
                $value = '';
        }
        
        return $value;
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