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
    protected $sentData;
    protected $request;

    public function __construct($field, PttEntityInfo $entityInfo, $sentData, Request $request, $languageCode)
    {
        $this->field = $field;
        $this->sentData = $sentData;
        $this->request = $request;
        $this->entityInfo = $entityInfo;
        $this->languageCode = $languageCode;
    }

    protected function _get($name = null)
    {
        $name = ($name) ? $name : $this->field['name'];
        return ($this->entityInfo->hasMethod('get' . $name, $this->languageCode)) ? $this->entityInfo->get($name, $this->languageCode) : null;
    }
}
