<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormAfterSave;

class PttFormAfterSaveGallery extends PttFormAfterSave
{
    private $em;
    public function perform()
    {
        $this->_saveRelatedEntities();
    }

    private function _saveRelatedEntities()
    {

        $this->em = $this->entityInfo->getEntityManager();

        $pttHelper = new PttHelperFormFieldTypeGallery($this->entityInfo, $this->field, $this->container, $this->entityInfo->getEntityManager());

        $index = 0;
        $ids = array();
        
        if (is_array($this->sentData) && count($this->sentData)) {
            foreach ($this->sentData as $key => $entityData) {
                if ($key != -1) {

                    $entity = $pttHelper->entityForDataArray($entityData);
                    $form = $pttHelper->formForEntity($entity, $key);

                    $form->setTotalData($index);
                    $form->isValid();
                    $form->save();

                    $ids[] = $entity->getPttId();
                    $index += 1;
                }
            }

        }

        $this->_deleteUnnecessaryRelations($ids);
    }

    private function _deleteTransEntities($module, $id){
        $entityRepository = $this->entityInfo->getBundle() . ':' . $module . 'Trans';

        $dql = '
            DELETE ' . $entityRepository . ' e WHERE e.relatedId = :id';

        $query = $this->em->createQuery($dql);
        $query->setParameter('id', $id);
        $query->execute();
    }

    private function _deleteUnnecessaryRelations($ids)
    {
        $entityRepository = $this->entityInfo->getBundle() . ':' . $this->field->options['entity'];

        $dql = '
        delete
            ' . $entityRepository . ' e
        where
            e.relatedId = :id and e._model = :model';
        if (count($ids)) {
            $dql .= '
            and
                e.id not in (' . implode(', ', $ids) . ')';
        }
        $query = $this->em->createQuery($dql);
        $query->setParameter('id', $this->entityInfo->get('pttId'));
        $query->setParameter('model', $this->entityInfo->getEntityName());
        $query->execute();
    }
}