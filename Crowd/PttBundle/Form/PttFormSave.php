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

    public function __construct($field, PttEntityInfo $entityInfo, Request $request, $sentData, $container, $languageCode = false)
    {
        $this->field = $field;
        $this->entityInfo = $entityInfo;
        $this->request = $request;
        $this->languageCode = $languageCode;
        $this->sentData = $sentData;
    }

    protected function _value()
    {
        return $this->entityInfo->get($this->field['name'], $this->languageCode);
    }

    protected function _sentValue($default = '')
    {
        return PttUtil::getFieldData($this->sentData, $this->entityInfo->getFormName(), $this->field['name'], $default, $this->languageCode);
    }
}
