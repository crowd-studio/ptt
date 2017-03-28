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

    public function __construct($field, $entity, $formSave, Request $request, $sentData, $container, $languageCode = false)
    {
        parent::__construct($field, $entity, $formSave, $request, $sentData, $container, $languageCode)
        $this->$faviconPath = $container->getParameter('favicon');
    }

    public function value()
    {
        $file = $this->request->files->get($this->entityInfo->getFormName())[$this->field['name']];

        if ($this->languageCode) {
            $file = (isset($files["Trans"][$this->languageCode][$this->field['name']])) ? $files["Trans"][$this->languageCode][$this->field['name']] : false;
        } else {
            $file = (isset($files[$this->field['name']])) ? $files[$this->field['name']] : false;
        }

        if ($file) {
            $this->field['options']['sizes'] = [['h' => 512, 'w' => 512]];
            $value = PttUploadFile::upload($file, $this->field);
            PttUploadFile::generateFavicon(
                $value,
                $this->faviconPath,
                $this->request->getSchemeAndHttpHost()
            );
        } else {
          if ($this->languageCode) {
              $deleteValue = (isset($this->sentData['check'][$this->entityInfo->getFormName()][$this->languageCode][$this->field['name']])) ? $this->sentData['check'][$this->entityInfo->getFormName()][$this->languageCode][$this->field['name']] : null;
          } else {
              $deleteValue = (isset($this->sentData['check'][$this->entityInfo->getFormName()][$this->field['name']])) ? $this->sentData['check'][$this->entityInfo->getFormName()][$this->field['name']] : null;
          }

          if ($deleteValue === 'true') {
              PttUploadFile::deleteFile($this->field, $value);
              PttUploadFile::deleteFavicons();
              $value = '';
          }
        }

        return ($value == null) ? $value : '';
    }
}
