<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormValue;

class PttFormFieldValueMultipleEntity extends PttFormFieldValue
{
    public function value()
    {
        if ($this->request->getMethod() == 'POST') {
            return ($this->sentData != null) ? $this->sentData : array();
        } else {
            return $this->_valueForRelatedEntities();
        }
    }

    private function _valueForRelatedEntities()
    {
        if (isset($this->field->options['modules'])) {
            $array = [];
            foreach ($this->field->options['modules'] as $key => $value) {
                array_push($array, $this->entityInfo->getPttServices()->getSimpleFilter($value['entity'], ['where' => ['relatedid' => $this->entityInfo->get('pttId'), 'model' => $this->field->getSimpleFormName()]]));
            }

            return $array;
        } else {
            return null;
        }
    }
}
