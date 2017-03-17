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
    private $faviconPath;

    public function __construct(PttField $field, PttEntityInfo $entityInfo, Request $request, $sentData, $container, $languageCode = false)
    {
        parent::__construct(PttField $field, PttEntityInfo $entityInfo, Request $request, $sentData, $container, $languageCode = false)
        $this->$faviconPath = $container->getParameter('favicon');
    }

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
                $this->faviconPath,
                $this->request->getSchemeAndHttpHost()
            );
        } else {
            $value = $this->_value();
            if ($this->languageCode) {
                $deleteValue = (isset($this->sentData[$this->languageCode][$this->field->name . '-delete']) ? $this->sentData[$this->languageCode][$this->field->name . '-delete'] : null;
            else {
                $deleteValue = (isset($this->sentData[$this->field->name . '-delete']) ? $this->sentData[$this->field->name . '-delete'] : null;
            }

            if($deleteValue && $deleteValue != 0) {
                PttUploadFile::deleteFile($deleteValue);
                PttUploadFile::deleteFavicons();
                $value = '';
            }
        }

        return ($value == null) ? $value : '';
    }

    private function _files()
    {
        if (strpos($this->entityInfo->getFormName(), '[') !== false) {
            $cleanName = str_replace(']', '', $this->entityInfo->getFormName());
            $cleanNameArr = explode('[', $cleanName);
            $files = $this->request->files->get($cleanNameArr[0]);

            for ($i=1; $i < count($cleanNameArr); $i++) {
                if (isset($files[$cleanNameArr[$i])) {
                    $files = $files[$cleanNameArr[$i];
                }
            }
            
            return $files;
        } else {
            return $this->request->files->get($this->entityInfo->getEntityName());
        }
    }
}
