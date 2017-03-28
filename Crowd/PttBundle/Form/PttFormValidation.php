<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttFormValidations;

class PttFormValidation
{
    protected $pttFormValidations;
    protected $sentData;
    protected $field;
    protected $container;

    public function __construct(PttFormValidations $pttFormValidations, $field, $sentData, $languageCode = false)
    {
        $this->pttFormValidations = $pttFormValidations;
        $this->container = $pttFormValidations->getForm()->getContainer();
        $this->field = $field;
        $this->sentData = $sentData;
    }

    protected function _sentValue($default = '')
    {
        return ($this->sentData != false) ? $this->sentData : $default;
    }
}
