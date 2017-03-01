<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PttFormSaveFavicon extends PttFormSave
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
            $this->field->options['sizes'] = [['h' => 512, 'w' => 512]];
            $value = PttUploadFile::upload($file, $this->field);
            PttUploadFile::generateFavicon(
                $value, 
                $this->container->getParameter('favicon'),
                $this->request->getSchemeAndHttpHost()
            );
        } else {
            $value = $this->entityInfo->get($this->field->name, $this->languageCode);
            if ($this->languageCode) {
                if (isset($this->sentData[$this->languageCode][$this->field->name . '-delete']) && $this->sentData[$this->languageCode][$this->field->name . '-delete'] != '0') {
                    $this->_deleteFile($this->sentData[$this->languageCode][$this->field->name . '-delete']);
                    $value = '';

                    PttUploadFile::deleteFavicons();
                }
            } else {
                if (isset($this->sentData[$this->field->name . '-delete']) && $this->sentData[$this->field->name . '-delete'] != '0') {
                    $this->_deleteFile($this->sentData[$this->field->name . '-delete']);
                    $value = '';
                }
            }
        }

        if ($value == null) {
                $value = '';
        }
        
        return $value;
    }

    private function _deleteFile($name)
    {
        PttUploadFile::deleteFile($name);
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