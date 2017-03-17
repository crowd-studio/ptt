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
    private $fieldEntity;

    public function __construct(PttEntityInfo $entityInfo, $fieldEntity)
    {
        $this->fieldEntity = $fieldEntity;

        $classNameArr = explode('\\', $entityInfo->getClassName());
        array_pop($classNameArr);
        $this->$relatedClassName =  implode('\\', $classNameArr) . '\\' . $this->fieldEntity;

        $this->entityInfo = $entityInfo;
    }

    public function cleanRelatedEntity()
    {
        $entity = new $this->relatedClassName();
        return $entity;
    }

    public function entityForDataArray($entityData)
    {
        if (!isset($entityData['id']) || $entityData['id'] == '') {
            $entity = $this->cleanRelatedEntity();
        } else {
            $entity = $this->entityInfo->getPttServices()->getOne($this->fieldEntity, $entityData['id']);
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
                $entity = $this->entityInfo->getPttServices()->getOne($this->fieldEntity, $entityData->getId());
            }
        } else {
            return $this->entityForDataArray($entityData);
        }

        return $entity;
    }

    public function formForEntity($entity, $key = false, $errors = false)
    {
        $pttForm = $this->entityInfo->getForm();
        $pttForm->setEntity($entity);

        if ($errors != false) {
            $pttForm->setErrors($errors);
        }
        if ($key === false) {
            $key = ($entity->getPttId() != null) ? $entity->getPttId() : '{{index}}';
        }

        $pttForm->setFormName($this->field->getFormName() . '[' . $key . ']');
        return $pttForm;
    }
}
