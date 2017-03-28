<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttSave;

class PttFormSave
{
    protected $field;
    protected $formSave;
    protected $request;
    protected $languageCode;
    protected $sentData;
    protected $entity;

    public function __construct($field, $entity, PttSave $formSave, Request $request, $sentData, $container, $languageCode = false)
    {
        $this->field = $field;
        $this->formSave = $formSave;
        $this->request = $request;
        $this->languageCode = $languageCode;
        $this->sentData = $sentData;
        $this->entity = $entity;
    }

    protected function _value()
    {
        return $this->formSave->get($this->field['name'], $this->languageCode);
    }

    protected function _sentValue($default = '')
    {
        return PttUtil::getFieldData($this->sentData, $this->formSave->getFormName(), $this->field['name'], $default, $this->languageCode);
    }
}
