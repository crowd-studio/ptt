<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldValueText extends PttFormFieldValue
{
    public function value()
    {
        $value = $this->_get();
        if (isset($this->field['disabled']) && $this->field['disabled']) {
            if (isset($this->field['field'])) {
                if (isset($this->field['entity'])) {
                    if (is_object($value)) {
                        $method = 'get' . ucfirst($this->field['field']);
                        $value = (method_exists($value, $method)) ? $value->$method() : '';
                    } else {
                        // Una entitat diferent
                        $entity = $this->entityInfo->getPttServices()->getOne($this->field['entity'], $value);

                        $method = 'get' . ucfirst($this->field['field']);
                        $value = (method_exists($entity, $method)) ? $entity->$method() : '';
                    }
                } else {
                    $value = $this->_get($this->field['field']);
                }
            }
        }
        
        return $value;
    }
}
