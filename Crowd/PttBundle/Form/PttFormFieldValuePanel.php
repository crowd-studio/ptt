<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormValue;

class PttFormFieldValuePanel extends PttFormFieldValue
{
    public function value()
    {
        if ($this->entityInfo->hasMethod('get' . ucfirst($this->field->name), $this->languageCode)) {
            $value = $this->entityInfo->get($this->field->name, $this->languageCode);
            if ($value == null && isset($this->field->options['options'])) {
                $value = '';
                foreach ($this->field->options['options'] as $key => $option) {
                    $value .= '0';
                }
            }
            return $value;
        } else {
            return null;
        }
    }
}
