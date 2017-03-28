<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttFormRender;
use Crowd\PttBundle\Util\PttFormSave;
use Crowd\PttBundle\Util\PttUtil;

class PttHelperFormFieldTypeEntity
{
    private $entityInfo;
    private $entity;
    private $pttForm;
    private $sentData;
    private $fields;
    private $formName;
    private $formId;

    public function __construct($entityInfo, $entity, $formName = '', $formId = '', $sentData = false)
    {
        $this->entityInfo = $entityInfo;
        $this->pttForm = $entityInfo->getForm();
        $this->entity = $entity;

        $this->sentData = $sentData;
        $this->formName = $formName;
        $this->formId = $formId;

        $this->fields = PttUtil::fields($this->pttForm->getContainer()->get('kernel'), $this->form->getBundle(), $this->entity->getClassName());
    }

    public function formForEntity($entity, $key = false)
    {
        $pttFormRender = new PttFormRender($this->pttForm, $entity, $this->fields, $this->formName, $this->formId);
        return $pttFormRender->perform('multi');
    }

    public function save()
    {
        $this->_updateFields();
        $pttFormSave = new PttFormSave($this->pttForm, $this->entity, $this->fields, $this->sentData);

        return $pttFormSave->perform();
    }

    public function validate()
    {
        $this->entity->beforeSave($this->sentData);

        $pttFormValidation = new pttFormValidation($this->pttForm, $this->entity, $this->sentData);
        $this->entity = $pttFormValidation->perform();
    }

    private function _updateFields()
    {
        foreach ($this->fields['block'] as $key => $block) {
            if ($block['static']) {
                foreach ($block['static'] as $field) {
                    $this->_updateField($field);
                }
            }

            if ($this->pttForm->getLanguages() && isset($block['trans'])) {
                foreach ($this->pttForm->getLanguages() as $language) {
                    foreach ($block['trans'] as $field) {
                        $this->_updateField($field, $language->getCode());
                    }
                }
            }
        }
    }

    private function _updateField($field, $languageCode = false)
    {
        if (PttUtil::isMapped($field)) {
            $value = PttClassNameGenerator::sentValue($field, $this->pttForm, $languageCode);
            $method = 'set' . $field['name'];

            if ($languageCode) {
                foreach ($this->entity->getTrans() as $key => $val) {
                    if ($val->getLanguage()->getCode() == $languageCode) {
                        $this->entity->getTrans()['$key']->$method($value);
                    }
                }
            } else {
                $this->entity->$method($value);
            }
        }
    }
}
