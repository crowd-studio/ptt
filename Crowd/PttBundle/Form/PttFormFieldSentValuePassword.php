<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldSentValuePassword extends PttFormFieldSentValue
{
    private $encoder;

    public function __construct($field, PttForm $pttForm, $languageCode = false)
    {
        parent::__construct($field, $pttForm, $languageCode);
        $this->encoder = $pttForm->getContainer()->get('security.encoder_factory')->getEncoder($this->entityInfo->getEntity());
    }
    public function value()
    {
        $value = (isset($this->sentData[$this->field['name']]['first'])) ? $this->sentData[$this->field['name']]['first'] : null;

        if ($value == null) {
            $value = $this->entityInfo->get($this->field['name'], $this->languageCode);
        } elseif (!$this->errors) {
            $value = $this->encoder->encodePassword($value, $this->entityInfo->get('salt'));
        }

        return $value;
    }
}
