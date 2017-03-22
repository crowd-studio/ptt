<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PttFormSaveImage extends PttFormSave
{
    public function value()
    {
        $file = $this->request->files->get($this->entityInfo->getFormName())[$this->field['name']];
        if ($file) {
            $value = PttUploadFile::upload($file, $this->field);
        } else {
            $value = $this->_value();
            if ($this->languageCode) {
                $deleteValue = (isset($this->sentData['check'][$this->entityInfo->getFormName()][$this->languageCode][$this->field['name']])) ? $this->sentData['check'][$this->entityInfo->getFormName()][$this->languageCode][$this->field['name']] : null;
            } else {
                $deleteValue = (isset($this->sentData['check'][$this->entityInfo->getFormName()][$this->field['name']])) ? $this->sentData['check'][$this->entityInfo->getFormName()][$this->field['name']] : null;
            }

            if ($deleteValue === 'true') {
                PttUploadFile::deleteFile($this->field, $value);
                $value = '';
            }
        }

        if ($value != '' && $this->field['type'] == 'gallery') {
            $path = $this->_sentValue(false);
            if ($path) {
                $nameArray = explode('/', $path);
                $originalName = end($nameArray);

                $uploadFile = new UploadedFile($path, $originalName, mime_content_type($path), filesize($path));
                $value = PttUploadFile::upload($uploadFile, $this->field);
            }
        }

        return ($value != null) ? $value : '';
    }
}
