<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttHelperFormFieldTypeEntity
{
    private $entityInfo;
    private $relatedClassName;
    private $entity;
    private $pttForm;

    public function __construct(PttEntityInfo $entityInfo, PttForm $pttForm, $entity)
    {
        $this->entityInfo = $entityInfo;
        $this->entity = $entity;
        $this->pttForm = $pttForm;
        $classNameArr = explode('\\', $this->entityInfo->getClassName());
        array_pop($classNameArr);
        $this->relatedClassName =  implode('\\', $classNameArr) . '\\' . $this->entity;
    }

    public function cleanRelatedEntity()
    {
        $this->getRelatedEntity();
    }

    protected function getCleanRelatedEntity()
    {
        $entity = new $this->relatedClassName();
        return $entity;
    }

    public function entityForDataArray($entityData)
    {
        if (!isset($entityData['id']) || $entityData['id'] == '') {
            $entity = $this->cleanRelatedEntity();
        } else {
            $entity = $this->entityInfo->getPttServices()->getOne($this->entity, $entityData['id']);
        }

        foreach ($entityData as $key => $value) {
            if ($key != 'id') {
                $methodName = 'set' . ucfirst($key);
                if (method_exists($entity, $methodName)) {
                    $entity->{$methodName}($value);
                }
            }
        }
        return $entity;
    }

    public function entityWithData($entityData)
    {
        if (is_object($entityData)) {
            if ($entityData->getPttId() == null) {
                $entity = $this->cleanRelatedEntity();
            } else {
                $entity = $this->entityInfo->getPttServices()->getOne($this->entity, $entityData->getId());
            }
        } else {
            return $this->entityForDataArray($entityData);
        }

        return $entity;
    }

    public function formForEntity($entity, $key = false)
    {
        $this->pttForm->setFormName($this->pttForm->getFormName() . '[' . $key . ']');
        $pttFormRender = new PttFormRender($this->pttForm, $entity, $this->fields);
        return $pttFormRender->perform();
    }

    public function save($entity, $sentData)
    {
        $pttFormSave = new PttFormSave($this->pttForm, $entity, $this->fields, $sentData);
        return $pttFormSave->perform();
    }
}
