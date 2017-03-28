<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttFormValidations;

class PttFormFieldSentValue
{
    protected $field;
    protected $sentData;
    protected $languageCode;
    protected $errors;

    public function __construct($field, PttFormValidations $pttFormValidations, $sentData, $languageCode = false)
    {
        $this->field = $field;
        $this->languageCode = $languageCode;
        $this->sentData = $sentData;
        $this->errors = $pttFormValidations->getForm()->getErrors($field['name'], $this->languageCode);
    }
}
