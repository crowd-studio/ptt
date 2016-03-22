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
            $array = array();
            foreach ($this->field->options['modules'] as $key => $value) {
                $dql = 'select e from ' . $this->entityInfo->getBundle() . ':' . $value['entity'] . ' e where e.relatedId = :id and e._model = :model';
                $em = $this->entityInfo->getEntityManager();
                $query = $em->createQuery($dql);
                $query->setParameter('id', $this->entityInfo->get('pttId'));
                $query->setParameter('model', $this->field->getSimpleFormName());
                array_push($array, $query->getResult());
            }

            return $array;
        } else {
            return null;
        }
    }
}