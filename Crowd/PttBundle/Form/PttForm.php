<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttTrans;

class PttForm
{
    private $securityContext;
    private $container;
    private $entityInfo;
    private $sentData;
    private $sentDataTrans;
    private $errors;
    private $request;
    private $languages;
    private $preferredLanguage;
    private $htmlFields;
    private $pttTrans;
    private $totalData = 0;
    private $twig;

    public function __construct(EntityManager $entityManager, TokenStorage $securityContext, ContainerInterface $serviceContainer)
    {
        $this->securityContext = $securityContext;
        $this->container = $serviceContainer;
        $this->twig = $this->container->get('twig');

        $metadata = $this->container->get('pttEntityMetadata');
        $this->languages = $metadata->getLanguages();
        $this->preferredLanguage = $metadata->getPreferredLanguage();

        $this->htmlFields = [];
    }

    public function setRequest($requestObj)
    {
        if (is_a($requestObj, 'Symfony\Component\HttpFoundation\RequestStack')) {
            $this->request = $requestObj->getCurrentRequest();
        } elseif (is_a($requestObj, 'Symfony\Component\HttpFoundation\Request')) {
            $this->request = $requestObj;
        }
    }

    public function setPttTrans($pttTrans)
    {
        $this->pttTrans = $pttTrans;
        $this->errors = new PttErrors($pttTrans);
    }

    public function getPttTrans()
    {
        return $this->pttTrans;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setEntity($entity)
    {
        $this->entityInfo = new PttEntityInfo($entity, $this->container, $this->languages, $this->pttTrans);
    }

    public function setFormName($formName)
    {
        $this->entityInfo->setFormName($formName);
    }

    public function getEntityInfo()
    {
        return $this->entityInfo;
    }

    public function getSentData($fieldName = false, $languageCode = false)
    {
        if ($fieldName != false) {
            if ($languageCode) {
                if (isset($this->sentData['Trans'][$languageCode][$fieldName])) {
                    return $this->sentData['Trans'][$languageCode][$fieldName];
                } elseif (isset($this->sentDataTrans['Trans'][$languageCode][$fieldName])) {
                    return $this->sentDataTrans['Trans'][$languageCode][$fieldName];
                } else {
                    return null;
                }
            } else {
                return (isset($this->sentData[$fieldName])) ? $this->sentData[$fieldName] : null;
            }
        } elseif ($languageCode) {
            return $this->sentData['Trans'][$languageCode];
        } else {
            return $this->sentData;
        }
    }

    public function setErrors($errors)
    {
        return $this->errors->set($errors);
    }

    public function getErrors($fieldName = false, $languageCode = false)
    {
        return $this->errors->get($fieldName, $languageCode);
    }

    public function addError($key, $message, $languageCode = false)
    {
        $this->errors->add($key, $message, $languageCode);
    }

    public function getErrorMessage()
    {
        return $this->entityInfo->getFields('errorMessage');
    }

    public function getSuccessMessage()
    {
        return $this->entityInfo->getFields('successMessage');
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function appendField($field)
    {
        $this->entityInfo->appendField($field);
    }

    public function createView($key = false)
    {
        $this->_makeHtmlFields();
        return ($key != false && $key != 'multi') ? $this->_createSingleView($key) : $this->_createGlobalView($key);
    }

    public function isValid()
    {
        $this->_updateSentData();

        $this->entityInfo->getEntity()->beforeSave($this->sentData);
        $this->_performFieldsLoopAndCallMethodNamed('_validateField');

        return !$this->errors->hasErrors();
    }

    public function save()
    {
        $this->_performFieldsLoopAndCallMethodNamed('_saveForField');


        if ($this->entityInfo->hasMethod('setTitle') && $this->entityInfo->hasMethod('getTitle')) {
            if (isset($this->sentData['Trans'][$this->preferredLanguage->getCode()]['title'])) {
                $this->entityInfo->set('title', $this->getSentData('title', $this->preferredLanguage->getCode()));
            }
        }

        if ($this->entityInfo->hasMethod('setSlug') && $this->entityInfo->hasMethod('getSlug')) {
            $this->entityInfo->set('slug', PttUtil::slugify((string)$this->entityInfo->getEntity()));
        }

        if (is_subclass_of($this->entityInfo->getEntity(), 'Crowd\PttBundle\Entity\PttEntity')) {
            $userId = -1;
            if ($this->securityContext->getToken() != null && method_exists($this->securityContext->getToken()->getUser(), 'getId')) {
                $userId = $this->securityContext->getToken()->getUser()->getId();
            }
            $this->entityInfo->set('updateObjectValues', $userId);
        }

        $entityPrincipal = $this->entityInfo->getEntity();

        if (!$entityPrincipal->getPttId()) {
            if (method_exists($entityPrincipal, 'set_Order')) {
                if (!$entityPrincipal->get_Order()) {
                    $entityPrincipal->set_Order(-1);
                }
            }
        }

        if (method_exists($entityPrincipal, 'updateTrans')) {
            $entityPrincipal->updateTrans($this->sentData['Trans']);
        }

        $this->container->get('pttServices')->persist($entityPrincipal);

        $entityPrincipal->afterSave($this->sentData);
        $this->_performFieldsLoopAndCallMethodNamed('_afterSaveForField');
    }

    private function _createSingleView($key)
    {
        return (isset($this->htmlFields[$key])) ? $this->htmlFields[$key] : 'Input ' . $key . ' not found';
    }

    private function _createGlobalView($key)
    {
        $html = '';
        $entityName = $this->entityInfo->getEntityName();
        $fields = $this->entityInfo->getFields();

        foreach ($fields->block as $i => $block) {
            if ($key == false) {
                $html .= '<div class="block-container well container-fluid"><div class="block-header header-line"><h4>' . $block . '</h4></div><div class="block-body row">';
            } else {
                $html .= '<div><div>';
            }

            if ($fields->static[$i]) {
                foreach ($fields->static[$i] as $field) {
                    if (isset($this->htmlFields[$field->name])) {
                        $html .= $this->htmlFields[$field->name];
                    } else {
                        $html .= 'pending to do ' . $field->type ;
                    }
                }
            }

            if ($this->languages && isset($fields->trans[$i]) && $fields->trans[$i]) {
                $html .= '<ul class="nav nav-tabs col-sm-12">';

                foreach ($this->languages as $k => $language) {
                    $active = ($k == 0) ? 'active' : '';
                    $error = ($this->errors->hasErrors($language->getCode())) ? ' error' : '';
                    $html .= '<li class="' . $active . $error . ' language-'. $language->getCode() .'"><a href="language-' . $language->getCode() . '" >' . $language->getTitle() . '</a></li>';
                }

                $html .= '</ul><div class="tab-content col-sm-12">';
                foreach ($this->languages as $k => $language) {
                    $active = ($k == 0) ? ' active' : '';
                    $html .= '<div class="tab-pane' . $active . ' language-' .$language->getCode()  . '">';
                    foreach ($fields->trans[$i] as $field) {
                        $html .= $this->htmlFields[$language->getCode()][$field->name];
                    }
                    $html .= '</div>';
                }

                $html .= '</div>';
            }
            $html .= '</div></div>';
        }


        return $html;
    }

    private function _makeHtmlFields()
    {
        if (!count($this->htmlFields)) {
            $fields = $this->entityInfo->getFieldsNew();
            foreach ($fields['block'] as $key => $block) {
                if ($block['static']) {
                    foreach ($block['static'] as $field) {
                        $field['value'] = $this->_newValueForField($field);
                        $info = [
                            'type' => $this->_getFieldType($field),
                            'params' => $field
                        ];

                        $this->htmlFields[$field['name']] = $this->twig->render('PttBundle:Form:factory.html.twig', $info);
                    }
                }
            }
        }
    }

    private function _getFieldType($field)
    {
        switch ($field['type']) {
        case 'text': case 'disabled': case 'email':
          return 'input';
          break;
        default:
          return $field['type'];
          break;
      }
    }

    private function _updateSentData()
    {
        if (strpos($this->entityInfo->getFormName(), '[') !== false) {
            $cleanName = str_replace(']', '', $this->entityInfo->getFormName());

            $cleanNameArr = explode('[', $cleanName);
            $sentData = [];

            foreach ($cleanNameArr as $i => $key) {
                if ($i == 0) {
                    $sentData = $this->request->get($key);
                } else {
                    if (isset($sentData[$key])) {
                        $sentData = $sentData[$key];
                    }
                }
            }
            $this->sentData = $sentData;

            $transEntity = [];
            if ($this->languages) {
                foreach ($this->languages as $language) {
                    $entityTrans = $this->entityInfo->getFormName() . '[Trans]';
                    $aux = $this->request->get($entityTrans);
                    if (isset($aux)) {
                        $transEntity[$language->getCode()] = reset($aux);
                    }
                }
            }
            $this->sentDataTrans = $transEntity;
        } else {
            $this->sentData = $this->request->get($this->entityInfo->getFormName());
            $transEntity = [];
        }
    }

    private function _performFieldsLoopAndCallMethodNamed($nameOfMethod)
    {
        $fields = $this->entityInfo->getFields();

        foreach ($fields->block as $key => $block) {
            if ($fields->static[$key]) {
                foreach ($fields->static[$key] as $field) {
                    $this->$nameOfMethod($field);
                }
            }

            if ($this->languages && isset($fields->trans[$key])) {
                foreach ($this->languages as $language) {
                    if ($fields->trans[$key]) {
                        foreach ($fields->trans[$key] as $field) {
                            $this->$nameOfMethod($field, $language->getCode());
                        }
                    }
                }
            }
        }
    }

    private function _validateField(PttField $field, $languageCode = false)
    {
        if ($field->validations) {
            foreach ($field->validations as $type => $message) {
                $validationClassName = PttClassNameGenerator::validation($type);
                $formValidation = new $validationClassName($this, $field, $languageCode);
                if (!$formValidation->isValid()) {
                    $this->errors->add($field->name, $message, $languageCode);
                }
            }
        }

        $fieldClassName = PttClassNameGenerator::field($field->type);
        $formField = new $fieldClassName($this, $field);

        $value = $this->_valueForField($field, $languageCode);

        if ($field->mapped) {
            $this->entityInfo->set($field->name, $value, $languageCode);
        }
    }

    private function _valueForField($type, $languageCode = false)
    {
        $sentValueClassName = PttClassNameGenerator::sentValue($type);
        $sentValue = new $sentValueClassName($field, $this, $languageCode);
        $value = $sentValue->value();

        return $value;
    }

    private function _saveForField(PttField $field, $languageCode = false)
    {
        $fieldClassName = PttClassNameGenerator::field($field->type);
        if ($field->mapped && strpos($fieldClassName, 'PttFormFieldTypeEntity') === false && strpos($fieldClassName, 'PttFormFieldTypeMultipleEntity') === false && strpos($fieldClassName, 'PttFormFieldTypeGallery') === false) {
            $saveClassName = PttClassNameGenerator::save($field->type);
            $formSave = new $saveClassName($field, $this->entityInfo, $this->request, $this->sentData, $this->container, $languageCode);
            $value = $formSave->value();
            if (strpos($fieldClassName, 'PttFormFieldTypeSelectMultiple') !== false) {
                $this->entityInfo->set($field->name . '_model', $this->sentData[$field->name . '_model'], $languageCode);
            }

            $this->entityInfo->set($field->name, $value, $languageCode);
        }
    }

    private function _afterSaveForField(PttField $field, $languageCode = false)
    {
        $afterSaveClassName = PttClassNameGenerator::afterSave($field->type);
        if ($afterSaveClassName) {
            $fieldClassName = PttClassNameGenerator::field($field->type);
            $formField = new $fieldClassName($this, $field);

            $name =  (strpos($fieldClassName, 'PttFormFieldTypeSelectMultiple') === false) ? $field->name : $field->name . '_model';
            $afterFormSave = new $afterSaveClassName($field, $this->entityInfo, $this->getSentData($name, $languageCode), $languageCode);

            $afterFormSave->perform();
        }
    }

    private function _newValueForField($field, $languageCode = false)
    {
        $className = PttClassNameGenerator::value($field['type']);
        $formValue = new $className($field, $this->entityInfo, $this->sentData, $this->request, $languageCode);
        return $formValue->value();
    }
}
