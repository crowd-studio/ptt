<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttClassNameGenerator
{
    public static function field($type)
    {
        return 'Crowd\PttBundle\Form\PttFormFieldType' . ucfirst($type);
    }

    public static function saveForField($field, $entity, $entityInfo, $request, $sentData, $container, $languageCode)
    {
        if (PttUtil::isMapped($field) && strtolower($field['type']) != 'multipleentity' && strtolower($field['type']) != 'gallery') {
            $value = PttClassNameGenerator::save($field, $entity, $entityInfo, $request, $sentData, $container, $languageCode);

            if (strtolower($field['type']) == 'selectmultiple') {
                $entityInfo->set($field['name'] . '_model', $sentData[PttUtil::fieldName($entity->getClassName(), $field['name'] . '_model', $languageCode)]);
            }

            $method = 'set' . ucfirst($field['name']);
            if ($languageCode) {
                if (method_exists($entity->getTrans()[0], $method)) {
                    foreach ($entity->getTrans() as $key => $val) {
                        if ($languageCode == $val->getLanguage()->getCode()) {
                            $entity->getTrans()[$key]->$method($value);
                        }
                    }
                }
            } else {
                if (method_exists($entity, $method)) {
                    $entity->$method($value);
                }
            }
        }
    }

    public static function save($field, $entity, $entityInfo, $request, $sentData, $container, $languageCode)
    {
        $name = 'Crowd\PttBundle\Form\PttFormSave';
        $className = $name . ucfirst($field['type']);
        $className = (class_exists($className)) ? $className : $name . 'Default';

        $formSave = new $className($field, $entity, $entityInfo, $request, $sentData, $container, $languageCode);
        return $formSave->value();
    }

    public static function sentValue($field, $form, $languageCode)
    {
        $name = 'Crowd\PttBundle\Form\PttFormFieldSentValue';
        $className = $name . ucfirst($field['type']);
        $className = (class_exists($className)) ? $className : $name . 'Default';

        $sentValue = new $className($field, $form, $languageCode);
        return $sentValue->value();
    }

    public static function afterSave($field, $entityInfo, $sentData, $formName, $languageCode)
    {
        $className = 'Crowd\PttBundle\Form\PttFormAfterSave' . ucfirst($field['type']);
        if (class_exists($className)) {
            $name =  (strtolower($field['type']) != 'selectmultiple') ? $field['type'] : $field['type'] . '_model';
            $sentData = PttUtil::getFieldData($sentData, $formName, $name, null, $languageCode);
            $afterFormSave = new $className($field, $entityInfo, $sentData, $languageCode);

            $afterFormSave->perform();
        }
    }

    public static function validation($field, $form, $languageCode)
    {
        if (isset($field['validations'])) {
            foreach ($field['validations'] as $type => $message) {
                $capitalizedType = '';
                $typeArr = explode('_', $type);
                foreach ($typeArr as $type) {
                    $capitalizedType .= ucfirst($type);
                }
                $className = 'Crowd\PttBundle\Form\PttFormValidation' . $capitalizedType;
                $formValidation = new $className($form, $field, $languageCode);
                if (!$formValidation->isValid()) {
                    $form->addError($field['name'], $message, $languageCode);
                }
            }
        }
    }

    public static function value($field, $entityInfo, $sentData, $entity, $request, $languageCode)
    {
        $className = 'Crowd\PttBundle\Form\PttFormFieldValue' . ucfirst($field['type']);
        $className = (!class_exists($className)) ? 'Crowd\PttBundle\Form\PttFormFieldValueDefault' : $className;
        $formValue = new $className($field, $entityInfo, $sentData, $entity, $request, $languageCode);
        return $formValue->value();
    }
}
