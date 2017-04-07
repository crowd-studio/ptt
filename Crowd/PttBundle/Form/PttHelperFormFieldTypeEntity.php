<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttFormRender;
use Crowd\PttBundle\Util\PttSave;
use Crowd\PttBundle\Util\PttFormValidations;
use Crowd\PttBundle\Util\PttUtil;

class PttHelperFormFieldTypeEntity
{
    private $entityInfo;
    private $pttForm;
    private $sentData;
    private $field;
    private $fields;
    private $formName;
    private $formId;
    private $rows;
    private $languageCode;

    public function __construct($entityInfo, $field, $languageCode, $formName = '', $formId = '', $sentData = false)
    {
        $this->entityInfo = $entityInfo;
        $this->pttForm = $entityInfo->getForm();
        $this->field = $field;
        $this->sentData = $sentData;
        $this->formName = $formName;
        $this->formId = $formId;
        $this->languageCode = $languageCode;

        $this->fields = PttUtil::fields($this->pttForm->getContainer()->get('kernel'), $this->pttForm->getBundle(), $this->field['entity']);

        $this->fields['block'][0]['static'][] = [
            'name' => 'id',
            'type' => 'hidden',
            'validations' => ['mapped' => false]
        ];
        // $this->fields['block'][0]['static'][] = [
        //     'name' => '_Order',
        //     'type' => 'hidden'
        // ];
    }

    public function className()
    {
        $className = get_class($this->entityInfo->getForm()->getEntity());
        $classNameArr = explode('\\', $className);
        array_pop($classNameArr);
        return implode('\\', $classNameArr) . '\\' . $this->field['entity'];
    }

    public function emptyForm()
    {
        $className = $this->className();
        $entity = new $className();

        $pttFormRender = new PttFormRender($this->pttForm, $entity, $this->fields, $this->formName, $this->formId);
        return $pttFormRender->perform('{key}');
    }

    public function formForEntity($entity, $key = false)
    {
        $pttFormRender = new PttFormRender($this->pttForm, $entity, $this->fields, $this->formName, $this->formId);
        return $pttFormRender->perform($key);
    }

    public function sentValue()
    {
        $array = $this->entityInfo->get($this->field['name'], $this->languageCode);
        if (is_array($this->sentData)) {
            // Esborrem els sobrers
          for ($iterator = $array->getIterator(); $iterator->valid(); $iterator->next()) {
              $exists = false;
              foreach ($this->sentData as $key => $obj) {
                  if (isset($obj['id'])) {
                      if ($iterator->current()->getPttId() == $obj['id']) {
                          $exists = true;
                      }
                  }
              }
              if (!$exists) {
                  $array->removeElement($iterator->current());
              }
          }

          // Sobreescrivim
          $i = 0;
            foreach ($this->sentData as $key => $obj) {
                $feat = false;
                if (isset($obj['id']) && $obj['id'] != '') {
                    for ($iterator = $array->getIterator(); $iterator->valid(); $iterator->next()) {
                        if ($iterator->current()->getPttId() == $obj['id']) {
                            $feat = $iterator->current();
                            $index = $iterator->key();
                        }
                    }
                    $update = true;
                } else {
                    $update = false;
                    $name = PttUtil::pttConfiguration('bundles')[0]["bundle"] . '\\Entity\\' . $this->field['entity'];
                    $feat = new $name();
                }

                if (method_exists($feat, 'set_Order')) {
                    $feat->set_Order($i);
                }

                $obj = [$this->field['name'] => $obj];
                $validation = new PttFormValidations($this->pttForm, $feat, $this->fields, $obj, $this->field['name']);
                $feat = $validation->perform();
                $save = new PttSave($this->pttForm, $feat, $this->fields, $obj);
                $feat = $save->perform();
                if ($update) {
                    $array->set($index, $feat);
                } else {
                    $array->add($this->addOne($feat));
                }
                $i++;
            }
        } else {
            $array = $this->sentData;
        }

        return $array;
    }

    private function addOne($new)
    {
        if (method_exists($new, 'setRelatedid')) {
            $new->setRelatedid($this->entityInfo->getEntity());
        }

        if (method_exists($new, 'setUpdateObjectValues')) {
            $new->setUpdateObjectValues(1);
        }

        if (method_exists($new, 'setSlug')) {
            $new->setSlug(PttUtil::slugify((string)$new));
        }

        return $new;
    }

    public function save($entity, $sentData)
    {
        $this->_updateFields();
        $pttFormSave = new PttSave($this->pttForm, $entity, $this->fields, $sentData);

        return $pttFormSave->perform();
    }

    public function validate()
    {
        $this->entity->beforeSave($this->sentData);

        $pttFormValidations = new PttFormValidations($this->pttForm, $this->entity, $this->fields, $this->sentData);
        return $pttFormValidations->perform();
    }
}
