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
        $entityRemains = [];
        $em = $this->entityInfo->getEntityManager();

        if (is_array($this->sentData) && count($this->sentData)) {
            $index = 0;
            foreach ($this->sentData as $key => $entityData) {
                if ($key != -1) {
                    $type = $entityData["type"];
                    $pttHelper = new PttHelperFormFieldTypeMultipleEntity($this->entityInfo, $this->field, $this->container, $em, $type);
                    $entity = $pttHelper->entityForDataArray($entityData);

                    $form = $pttHelper->formForEntity($entity, $key);
                    $form->setTotalData($index);

                    $form->isValid();
                    $form->save(true);
                        
                    $index += 1;
                    if (isset($entityRemains[$type])){
                        $entityRemains[$type] = $entityRemains[$type] . ',' . $entity->getPttId();
                    } else {
                        $entityRemains[$type] = $entity->getPttId();    
                    }
                }
            }
        }
        $this->_deleteUnnecessaryRelations($entityRemains, $em);
    }


    private function _deleteUnnecessaryRelations($entityRemains, $em)
    {
        $schema = $em->getConnection()->getSchemaManager();
        foreach ($this->field->options['modules'] as $key => $value)
        {
            $entityRepository = $this->entityInfo->getBundle() . ':' . $value['entity'];

            $dql = '
            delete ' . $entityRepository . ' e
            where e.relatedId = :id and e._model = :model';
            if (isset($entityRemains[$value['entity']]) && count($entityRemains[$value['entity']])) {
                $dql .= '
                and e.id not in (' . $entityRemains[$value['entity']] . ')';
            }

            $query = $em->createQuery($dql);
            $query->setParameter('id', $this->entityInfo->get('pttId'));
            $query->setParameter('model', $this->entityInfo->getEntityName());
            $query->execute();

            if($schema->tablesExist([$value['entity'].'Trans']) === true){
                $dql = '
                DELETE ' . $entityRepository . 'Trans e 
                WHERE e.relatedId NOT IN (SELECT a.id FROM '. $entityRepository.' a)';
                $query = $em->createQuery($dql);
                $query->execute();
            }
        }
    }
}