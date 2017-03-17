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
    private $pttServices;

    public function perform()
    {
        $this->_saveRelatedEntities();
    }

    private function _saveRelatedEntities()
    {
        $entityRemains = [];
        $this->pttServices = $this->container->get('pttServices');

        if (is_array($this->sentData) && count($this->sentData)) {
            $index = 0;
            foreach ($this->sentData as $key => $entityData) {
                if ($key != -1) {
                    $type = $entityData["type"];
                    $pttHelper = new PttHelperFormFieldTypeMultipleEntity($this->entityInfo, $this->field, $this->container, $type);

                    $entity = $pttHelper->entityForDataArray($entityData);
                    $form = $pttHelper->formForEntity($entity, $key);

                    $form->isValid();
                    $form->save();
                    $index += 1;
                    if (isset($entityRemains[$type])) {
                        $entityRemains[$type] = $entityRemains[$type] . ',' . $entity->getPttId();
                    } else {
                        $entityRemains[$type] = $entity->getPttId();
                    }
                }
            }
        }
        $this->_deleteUnnecessaryRelations($entityRemains);
    }

    private function _deleteUnnecessaryRelations($entityRemains)
    {
        foreach ($this->field->options['modules'] as $key => $value) {
            $where = [
                ['column' => 'relatedid', 'operator' => '=', 'value' => $this->entityInfo->get('pttId')],
                ['column' => 'model', 'operator' => '=', 'value' => $this->entityInfo->getEntityName()]
            ];

            if (isset($entityRemains[$value['entity']]) && count($entityRemains[$value['entity']])) {
                $where[] = ['column' => 'id', 'operator' => 'NOT IN', 'value' => $entityRemains[$value['entity']]];
            }

            $result = $this->pttServices->get($value['entity'], ['where' => [['and' => $where]]]);
            $this->pttServices->removeAll($result);
        }
    }
}
