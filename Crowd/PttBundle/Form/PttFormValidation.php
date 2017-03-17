<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormValidation
{
    protected $entityInfo;
    protected $sentData;
    protected $field;
    protected $languageCode;
    protected $pttForm;

    public function __construct(PttForm $pttForm, PttField $field, $languageCode = false)
    {
        $this->pttForm = $pttForm;
        $this->entityInfo = $pttForm->getEntityInfo();
        $this->field = $field;
        $this->languageCode = $languageCode;
        $this->sentData = $pttForm->getSentData($this->field->name, $this->languageCode);
    }

    protected function _sentValue($default = '')
    {
        return ($this->sentData != false) ? $this->sentData : $default;
    }
}
