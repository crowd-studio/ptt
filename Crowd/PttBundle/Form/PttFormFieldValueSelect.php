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
                $method = 'get' . ucfirst($this->field->name);
                return $this->entityInfo->getEntity()->$method();
            }
        } else {
            if ($this->field->mapped) {
                if(isset($this->field->options['search']) && $this->field->options['search']){
                    if($this->entityInfo->get($this->field->name, $this->languageCode) != ''){
                        return $this->entityInfo->get($this->field->name, $this->languageCode);
                    } else {
                        return null;
                    }
                } else {
                    return $this->entityInfo->get($this->field->name, $this->languageCode);    
                }

            } else {
                return null;
            }
        }
    }
}