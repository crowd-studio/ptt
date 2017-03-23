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

    public static function saveForField($field, $formName, $entityInfo, $request, $sentData, $container, $languageCode)
    {
        if (PttUtil::isMapped($field) && strtolower($field['type']) != 'entity' && strtolower($field['type']) != 'multipleentity' && strtolower($field['type']) != 'gallery') {
            $value = PttClassNameGenerator::save($field, $entityInfo, $request, $sentData, $container, $languageCode);

            if (strtolower($field['type']) == 'selectmultiple') {
                $entityInfo->set($field['name'] . '_model', $sentData[PttUtil::fieldName($formName, $field['name'] . '_model', $languageCode)]);
            }

            $entityInfo->set($field['name'], $value, $languageCode);
        }
    }

    public static function save($field, $entityInfo, $request, $sentData, $container, $languageCode)
    {
        $name = 'Crowd\PttBundle\Form\PttFormSave';
        $className = $name . ucfirst($field['type']);
        $className = (class_exists($className)) ? $className : $name . 'Default';

        $formSave = new $className($field, $entityInfo, $request, $sentData, $container, $languageCode);
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
                return new $className($form, $field, $languageCode);
                if (!$formValidation->isValid()) {
                    $form->errors->add($field['name'], $message, $languageCode);
                }
            }
        }
    }

    public static function value($field, $entityInfo, $sentData, $request, $languageCode)
    {
        $className = 'Crowd\PttBundle\Form\PttFormFieldValue' . ucfirst($field['type']);
        $className = (!class_exists($className)) ? 'Crowd\PttBundle\Form\PttFormFieldValueDefault' : $className;
        $formValue = new $className($field, $entityInfo, $sentData, $request, $languageCode);
        return $formValue->value();
    }
}
