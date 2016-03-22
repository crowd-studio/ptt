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
                return ($this->sentData != null) ? $this->sentData : array();
            } else {
                return $this->_valueForMultipleRelations();
            }
        } else {
            if ($this->field->mapped) {
                return $this->entityInfo->get($this->field->name, $this->languageCode);
            } else {
                return null;
            }
        }
    }

    private function _valueForMultipleRelations()
    {
        $multipleInfo = $this->field->options['multiple'];
        if (is_array($multipleInfo)) {
            if ($this->entityInfo->get('pttId') != null) {

                $me = $multipleInfo['me'];
                $them = $multipleInfo['them'];

                $dql = '
                select
                    e.' . $them . ' id
                from
                    ' . $this->entityInfo->getBundle() . ':' . $multipleInfo['relatingEntity'] . ' e
                where
                    e.' . $me . ' = :id
                ';

                $em = $this->entityInfo->getEntityManager();
                $query = $em->createQuery($dql);
                $query->setParameter('id', $this->entityInfo->get('pttId'));
                $results = $query->getResult();

                $ids = array();
                foreach ($results as $result) {
                    $ids[] = $result['id'];
                }

                return $ids;
            } else {
                return array();
            }
        } else {
            throw new \Exception('The multiple key must be an array (entity, me, them) for field ' . $this->field->name);
        }
    }
}