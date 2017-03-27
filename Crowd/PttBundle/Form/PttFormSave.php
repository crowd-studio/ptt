<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Crowd\PttBundle\Util\PttUtil;

class PttFormSave
{
    protected $field;
    protected $entityInfo;
    protected $request;
    protected $languageCode;
    protected $sentData;
    protected $entity;

    public function __construct($field, $entity, PttEntityInfo $entityInfo, Request $request, $sentData, $container, $languageCode = false)
    {
        $this->field = $field;
        $this->entityInfo = $entityInfo;
        $this->request = $request;
        $this->languageCode = $languageCode;
        $this->sentData = $sentData;
        $this->entity = $entity;
    }

    private function _methodExists($name)
    {
        return ($this->languageCode) ? method_exists($this->entity->getTrans()[0], $name) : method_exists($this->entity, $name);
    }

    protected function _value()
    {
        $name = 'get' . ucfirst($this->field['name']);

        return ($this->_methodExists($name)) ? $this->_fieldValue($name) : null;
    }

    private function _fieldValue($name)
    {
        return ($this->languageCode) ? $this->_transValue($name) : $this->entity->$name();
    }

    private function _transValue($name)
    {
        $val = null;
        foreach ($this->entity->getTrans() as $value) {
            if ($this->languageCode == $value->getLanguage()->getCode()) {
                $val = $value->$name();
            }
        }

        return $val;
    }

    protected function _sentValue($default = '')
    {
        return PttUtil::getFieldData($this->sentData, $this->entityInfo->getFormName(), $this->field['name'], $default, $this->languageCode);
    }
}
