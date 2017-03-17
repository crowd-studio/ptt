<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PttFormSaveSelectMultiple extends PttFormSave
{
    public function value()
    {
        $pttServices = $this->entityInfo->getPttServices();
        $entityModel = null;

        $id = $this->_sentValue(0);

        if ($this->languageCode) {
            $model = $this->sentData[$this->languageCode][$this->field->name . '_model'];
            $entityModel = $pttServices->getSimpleFilter($model . 'Trans', ['where' => ['relatedid' => $id, 'language' => $this->languageCode]]);
            $entityModel = (isset($entityModel[0])) ? $entityModel[0] : null;
        } else {
            $model = $this->sentData[$this->field->name . '_model'];
            $entityModel = $pttServices->getOne($model, $id);
        }

        if ($entityModel) {
            $title = (method_exists($entityModel, 'getTitle')) ? $entityModel->getTitle() : '';
            $this->entityInfo->set($this->field->name . '_title', $title, $this->languageCode);
        }

        return $this->_value();
    }
}
