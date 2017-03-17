<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormValue;

class PttFormFieldValueMultipleEntity extends PttFormFieldValue
{
    private pttServices;

    public function __construct(PttField $field, PttEntityInfo $entityInfo, $sentData, Request $request, $languageCode)
    {
        parent::__construct($field, $entityInfo, $sentData, $request, $languageCode);
        $this->pttServices = $entityInfo->getPttServices();
    }

    public function value()
    {
        if ($this->request->getMethod() == 'POST') {
            return ($this->sentData != null) ? $this->sentData : [];
        } else {
            return $this->_valueForRelatedEntities();
        }
    }

    private function _valueForRelatedEntities()
    {
        if (isset($this->field->options['modules'])) {
            $array = [];
            foreach ($this->field->options['modules'] as $key => $value) {
                array_push($array, $this->pttServices->getSimpleFilter($value['entity'], ['where' => ['relatedid' => $this->_get('pttId'), 'model' => $this->field->getSimpleFormName()]]));
            }

            return $array;
        } else {
            return null;
        }
    }
}
