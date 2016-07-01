<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormAfterSave;
use Crowd\PttBundle\Util\PttUtil;

class PttFormAfterSaveMultipleEntity extends PttFormAfterSave
{
    public function perform()
    {
        $this->_saveRelatedEntities();
    }

    private function _saveRelatedEntities()
    {
        $ids = array();
        $model = false;

        if (is_array($this->sentData) && count($this->sentData)) {
            $index = 0;
            foreach ($this->sentData as $key => $entityData) {
                if ($key != -1) {
                    $em = $this->entityInfo->getEntityManager();
                    $pttHelper = new PttHelperFormFieldTypeMultipleEntity($this->entityInfo, $this->field, $this->container, $em, $entityData["type"]);

                    $entity = $pttHelper->entityForDataArray($entityData);
                    $form = $pttHelper->formForEntity($entity, $key);
                    $form->setTotalData($index);
                    $form->save();
                    $ids[] = $entity->getPttId();
                    $index += 1;
                    $model = '';
                    foreach ($this->sentData as $key => $module) {
                        $model = $module['_model'];
                        break; 
                    }
                }
            }
        }
        $this->_deleteUnnecessaryRelations($ids, $model);
    }

    private function _deleteTransEntities($module, $id){
        $em = $this->entityInfo->getEntityManager();
        $entityRepository = $this->entityInfo->getBundle() . ':' . $module . 'Trans';

        $dql = 'delete ' . $entityRepository . ' e WHERE e.relatedId = :id';

        $query = $em->createQuery($dql);
        $query->setParameter('id', $id);
        $query->execute();
    }

    private function _deleteUnnecessaryRelations($ids, $model)
    {

        $em = $this->entityInfo->getEntityManager();

        foreach ($this->field->options['modules'] as $key => $value)
        {
            $entityRepository = $this->entityInfo->getBundle() . ':' . $value['entity'];

            $dql = '
            delete
                ' . $entityRepository . ' e
            where
                e.relatedId = :id';
            if (count($ids)) {
                $dql .= '
                and
                    e.id not in (' . implode(', ', $ids) . ')';
            }

            if ($model){
                $dql .= " and e._model = '" . $model . "'";
            }

            $query = $em->createQuery($dql);
            $query->setParameter('id', $this->entityInfo->get('pttId'));
            $query->execute();
        }


    }
}