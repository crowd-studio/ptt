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
            foreach ($this->sentData as $key => $entityData) {
                if ($key != -1) {
                    $type = $entityData["type"];
                    $pttHelper = new PttHelperFormFieldTypeMultipleEntity($this->entityInfo, $this->field, $this->container, $em, $type);

                    $entityName = $this->entityInfo->getBundle() . ':' . 'Material';
                    $entityData['material'] = $em->find($entityName, $entityData['material']);

                    $entityData['creationDate'] = new \DateTime('now');
                    $entityData['updateDate'] = new \DateTime('now');
                    $entityData['creationUserId'] = 1;
                    $entityData['updateUserId'] = 1;
                    $entityData['slug'] = 'hack by crowd team';
                    $entityNameFather = $this->entityInfo->getBundle() . ':' . 'Petition';
                    $entityData['petition'] =  $em->find($entityNameFather, $this->entityInfo->get('pttId'));;

                    //var_dump();die();

                    //$entityData['material']->setCreationDate(New Date());


                    $entity = $pttHelper->entityForDataArray($entityData);



                    $em->persist($entity);
            		$em->flush();
                    if (!is_null($entity->getPttId()) ){
                        if(!isset($entityRemains[$type])){
                            $entityRemains[$type] = $entity->getPttId();
                        }else{
                            $entityRemains[$type] = $entityRemains[$type] . ',' . $entity->getPttId();
                        }
                    }
                }
            }
        }
        $this->_deleteUnnecessaryRelations($entityRemains, $em);
    }

    private function _deleteTransEntities($module, $id){
        $em = $this->entityInfo->getEntityManager();
        $entityRepository = $this->entityInfo->getBundle() . ':' . $module . 'Trans';

        $dql = 'delete ' . $entityRepository . ' e WHERE e.relatedid = :id';

        $query = $em->createQuery($dql);
        $query->setParameter('id', $id);
        $query->execute();
    }

    private function _deleteUnnecessaryRelations($entityRemains, $em)
    {
        foreach ($this->field->options['modules'] as $key => $value)
        {
            $entityRepository = $this->entityInfo->getBundle() . ':' . $value['entity'];

            $dql = '
            delete ' . $entityRepository . ' e
            where e.' . strtolower($this->entityInfo->getEntityName()) . ' = :id and e._model = :model';
            if (isset($entityRemains[$value['entity']]) && count($entityRemains[$value['entity']])) {
                $dql .= '
                and e.id not in (' . $entityRemains[$value['entity']] . ')';
            }

            $query = $em->createQuery($dql);
            $query->setParameter('model', $this->entityInfo->getEntityName());
            $query->setParameter('id', $this->entityInfo->get('pttId'));
            $query->execute();
        }


    }
}
