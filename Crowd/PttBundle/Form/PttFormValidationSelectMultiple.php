<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormValidationSelectMultiple extends PttFormValidation
{
    private $sentDataTitle;

    public function __construct(PttForm $pttForm, $field, $languageCode = false)
    {
        parent::__construct($pttForm, $field, $languageCode);
        $this->sentDataTitle = $this->pttForm->getSentData($this->field['name'] . '_title', $languageCode);
    }

    public function isValid()
    {
        return ($this->_sentValue() !== '' && $this->sentDataTitle !== '');
    }
}
