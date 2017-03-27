<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\HttpFoundation\Request;

class PttFormFieldValue
{
    protected $field;
    protected $entityInfo;
    protected $languageCode;
    protected $entity;
    protected $sentData;
    protected $request;

    public function __construct($field, PttEntityInfo $entityInfo, $entity, $sentData, Request $request, $languageCode)
    {
        $this->field = $field;
        $this->sentData = $sentData;
        $this->request = $request;
        $this->entityInfo = $entityInfo;
        $this->entity = $entity;
        $this->languageCode = $languageCode;
    }

    protected function _get($name = null)
    {
        $name = ($name) ? $name : $this->field['name'];
        $name = 'get' . ucfirst($name);

        return ($this->_methodExists($name)) ? $this->_value($name) : null;
    }

    private function _value($name)
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

    private function _methodExists($name)
    {
        return ($this->languageCode) ? method_exists($this->entity->getTrans()[0], $name) : method_exists($this->entity, $name);
    }
}
