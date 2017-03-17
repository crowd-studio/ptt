<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormValue;

class PttFormFieldValueSelect extends PttFormFieldValue
{
    public function value()
    {
        $multiple = (isset($this->field->options['multiple']));
        if ($this->field->options['type'] == 'entity' && $multiple) {
            if ($this->request->getMethod() == 'POST') {
                return ($this->sentData != null) ? $this->sentData : [];
            } else {
                return $this->_get('get' . ucfirst($this->field->name));
            }
        } else {
            if ($this->field->mapped) {
                if (isset($this->field->options['search']) && $this->field->options['search']) {
                    return ($this->_get() != '') ? $this->_get() : null;
                } else {
                    return $this->_get();
                }
            } else {
                return null;
            }
        }
    }
}
