<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttHelperFormFieldTypeMultipleEntity
{

    private $entityInfo;
    private $field;
    private $container;
    private $em;
    private $entity;

    public function __construct(PttEntityInfo $entityInfo, PttField $field, $container = false, $em = false, $entity)
    {
        $this->entityInfo = $entityInfo;
        $this->field = $field;
        $this->container = $container;
        $this->entity = $entity;
        $this->em = $em;
    }

    public function classNameForRelatedEntity()
    {
        $classNameArr = explode('\\', $this->entityInfo->getClassName());
        array_pop($classNameArr);
        return implode('\\', $classNameArr) . '\\' . $this->entity;

    }

    public function cleanRelatedEntity()
    {
        $className = $this->classNameForRelatedEntity();
        $entity = new $className();
        $entity->setRelatedId($this->entityInfo->get('pttId'));
        return $entity;
    }

    public function entityForDataArray($entityData)
    {
        $className = $this->classNameForRelatedEntity();

        if (!isset($entityData['id']) || $entityData['id'] == '') {
            $entity = new $className();
            $entity->setRelatedId($this->entityInfo->get('pttId'));
        } else {
            $entity = $this->em->getRepository($this->entityInfo->getBundle() . ':' . $this->entity)->findOneBy(array('id' => $entityData['id']));
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
        $className = $this->classNameForRelatedEntity();

        if (is_object($entityData)) {
            if ($entityData->getPttId() == null) {
                $entity = new $className();
                $entity->setRelatedId($this->entityInfo->get('pttId'));
            } else {
                $entity = $this->em->getRepository($this->entityInfo->getBundle() . ':' . $this->entity)->findOneBy(array('id' => $entityData->getId()));
            }
        } else {
            return $this->entityForDataArray($entityData);
        }

        return $entity;
    }

    public function formForEntity($entity, $key = false, $errors = false)
    {
        $pttForm = $this->container->get('pttForm');

        $pttForm->setEntity($entity);

        if ($errors != false) {
            $pttForm->setErrors($errors);
        }

        if ($key == false) {
            $key = ($entity->getPttId() != null) ? $entity->getPttId() : '{{index}}';
        }

        $pttForm->setFormName($this->field->getFormName() . '[' . $key . ']');

        return $pttForm;
    }
}