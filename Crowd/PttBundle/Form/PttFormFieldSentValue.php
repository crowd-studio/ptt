<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\HttpFoundation\Request;

class PttFormFieldSentValue
{
    protected $field;
    protected $entityInfo;
    protected $sentData;
    protected $languageCode;
    protected $errors;

    public function __construct(PttField $field, PttForm $pttForm, $languageCode = false)
    {
        $this->field = $field;
        $this->languageCode = $languageCode;
        $this->entityInfo = $pttForm->getEntityInfo();
        $this->sentData = $pttForm->getSentData($this->field->name, $this->languageCode);
        $this->errors = $pttForm->getErrors($this->field->name, $this->languageCode);
    }
}
