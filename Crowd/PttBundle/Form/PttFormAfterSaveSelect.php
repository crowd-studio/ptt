<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormAfterSave;

class PttFormAfterSaveSelect extends PttFormAfterSave
{
    public function perform()
    {
        $multiple = (isset($this->field->options['multiple']));
        if ($this->field->options['type'] == 'entity' && $multiple) {
            $this->_saveMultipleRelations();
        }
    }

    private function _saveMultipleRelations()
    {
        $multipleInfo = $this->field->options['multiple'];
        if (is_array($multipleInfo)) {

            $em = $this->entityInfo->getEntityManager();

            $currentIds = $this->_deleteUnnecessaryRelations();

            if (is_array($this->sentData) && count($this->sentData)) {

                $relatingEntityClassName = $this->_classNameForRelatingEntity();

                foreach ($this->sentData as $relatedId) {

                    if (!in_array($relatedId, $currentIds)) {

                        $relatingEntity = new $relatingEntityClassName();

                        $methodName = 'set' . ucfirst($multipleInfo['me']);
                        $relatingEntity->{$methodName}($this->entityInfo->get('pttId'));

                        $methodName = 'set' . ucfirst($multipleInfo['them']);
                        $relatingEntity->{$methodName}($relatedId);

                        $em->persist($relatingEntity);
                    }
                }
                $em->flush();
            }
        } else {
            throw new \Exception('The multiple key must be an array (entity, me, them) for field ' . $this->field->name);
        }
    }

    private function _classNameForRelatingEntity()
    {
        $multipleInfo = $this->field->options['multiple'];
        $classNameArr = explode('\\', $this->entityInfo->getClassName());
        array_pop($classNameArr);
        return implode('\\', $classNameArr) . '\\' . $multipleInfo['relatingEntity'];
    }

    private function _deleteUnnecessaryRelations()
    {
        $em = $this->entityInfo->getEntityManager();
        $ids = (is_array($this->sentData) && count($this->sentData)) ? array_values($this->sentData) : array();
        $multipleInfo = $this->field->options['multiple'];

        $me = $multipleInfo['me'];
        $them = $multipleInfo['them'];

        $relatingEntityRepository = $this->entityInfo->getBundle() . ':' . $multipleInfo['relatingEntity'];

        $dql = '
        delete
            ' . $relatingEntityRepository . ' e
        where
            e.' . $me . ' = :me';
        if (count($ids)) {
            $dql .= '
            and
            e.' . $them . ' not in (:ids)';
        }

        $query = $em->createQuery($dql);
        $query->setParameter('me', $this->entityInfo->get('pttId'));
        if (count($ids)) {
            $query->setParameter('ids', $ids);
        }
        $query->execute();

        $dql = '
        select
            e.' . $them . ' id
        from
            ' . $relatingEntityRepository . ' e
        where
            e.' . $me . ' = :id
        ';
        $query = $em->createQuery($dql);
        $query->setParameter('id', $this->entityInfo->get('pttId'));
        $results = $query->getResult();

        $ids = array();
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }
        return $ids;
    }
}