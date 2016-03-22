<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormValue;

class PttFormFieldValueGallery extends PttFormFieldValue
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
        $dql = '
        select
            e
        from
            ' . $this->entityInfo->getBundle() . ':' . $this->field->options['entity'] . ' e
        where
            e.relatedId = :id and e._model = :model
        ';
        if (isset($this->field->options['sortBy']))
        {

            $dql .= ' order by ';
            foreach ($this->field->options['sortBy'] as $key => $value) {
                $dql .= ' e.' . $key . ' ' . $value .',';
            }
            
            $dql = trim($dql, ',');
        } else {
            $dql .= ' order by e._order asc';
        }

        $em = $this->entityInfo->getEntityManager();
        $query = $em->createQuery($dql);
        $query->setParameter('id', $this->entityInfo->get('pttId'));
        $query->setParameter('model', $this->field->getSimpleFormName());
        return $query->getResult();
    }
}