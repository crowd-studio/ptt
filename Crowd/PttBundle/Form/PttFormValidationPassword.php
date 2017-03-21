<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormValidationPassword extends PttFormValidation
{
    private $sentDataRepeated;
    public function __construct(PttForm $pttForm, $field, $languageCode = false)
    {
        parent::__construct($pttForm, $field, $languageCode);
        $this->sentDataRepeated = $pttForm->getSentData($this->field['name'] '_repeated', $languageCode);
    }

    public function isValid()
    {
        $value = (isset($this->sentData)) ? $this->sentData : '';
        $repeatedValue = (isset($this->sentDataRepeated)) ? $this->sentDataRepeated : '';

        $originalValue = $this->entityInfo->get($this->field->name);


        if (trim($value) != '') {
            if (strlen(trim($value)) < 6) {
                return false;
            } else {
                return (trim($value) == trim($repeatedValue));
            }
        } else {
            return ($originalValue != '');
        }
    }
}
