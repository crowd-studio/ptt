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
    private $formName;

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
        $this->formName = $this->entityInfo->getEntityName();
    }

    public function setFormName($formName)
    {
        $this->entityInfo->setFormName($formName);
    }

    // public function getFormName($fieldName, $languageCode = false, $append = '')
    // {
    //     return ($languageCode) ? $this->entityInfo->getFormName() . '[' . $languageCode . '][' . $fieldName . ']' . $append : $this->entityInfo->getFormName() . '[' . $fieldName . ']' . $append;
    // }
    //
    // public function getFormId($fieldName, $languageCode = false, $append = '')
    // {
    //     return str_replace('--', '-', str_replace('[', '-', str_replace(']', '', $this->getCompleteFormName($fieldName, $languageCode, $append))));
    // }

    public function getEntityInfo()
    {
        return $this->entityInfo;
    }

    public function getSentData($fieldName = false, $languageCode = false)
    {
        if ($fieldName != false) {
            return PttUtil::getFieldData($this->sentData, $this->formName, $fieldName, null, $languageCode);
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
            if ($this->getSentData('title', $this->preferredLanguage->getCode())) {
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

        // if (method_exists($entityPrincipal, 'updateTrans')) {
        //     $entityPrincipal->updateTrans($this->sentData);
        // }

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

        foreach ($fields['block'] as $i => $block) {
            if ($key == false) {
                $html .= '<div class="block-container well container-fluid"><div class="block-header header-line"><h4>' . $block['title'] . '</h4></div><div class="block-body row">';
            } else {
                $html .= '<div><div>';
            }

            if ($block['static']) {
                foreach ($block['static'] as $field) {
                    if (isset($this->htmlFields[$field['name']])) {
                        $html .= $this->htmlFields[$field['name']];
                    } else {
                        $html .= 'pending to do ' . $field['type'] ;
                    }
                }
            }

            if ($this->languages && isset($this->htmlFields['Trans'])) {
                $html .= '<div class="form-group col-xs-12"><div class="tabs"><ul class="tab-nav list-inline">';

                foreach ($this->languages as $k => $language) {
                    $error = ($this->errors->hasErrors($language->getCode())) ? ' error' : '';
                    $html .= '<li class="' . $error . '"><a data-toggle="#' . strtolower($block['title']) . '-' . $language->getCode() . '" ng-click="changeTabEvent($event)">' . $language->getTitle() . '</a></li>';
                }

                $html .= '</ul><div class="tab-content">';
                foreach ($this->languages as $k => $language) {
                    $id = strtolower($block['title']) . '-' . $language->getCode();
                    $html .= '<div id="' . $id . '" class="tab-pane"><div class="container-fluid"><div class="row">';
                    foreach ($this->htmlFields['Trans'][$language->getCode()] as $fields) {
                        $html .= $fields;
                    }
                    $html .= '</div></div></div>';
                }

                $html .= '</div></div></div>';
            }
            $html .= '</div></div>';
        }


        return $html;
    }

    private function _makeHtmlFields()
    {
        if (!count($this->htmlFields)) {
            $fields = $this->entityInfo->getFieldsNew();

            $this->htmlFields = $this->_getExtraFields();

            foreach ($fields['block'] as $key => $block) {
                if ($block['static']) {
                    foreach ($block['static'] as $field) {
                        $this->htmlFields[$field['name']] = $this->_renderField($field);
                    }
                }

                if (isset($block['trans'])) {
                    foreach ($this->languages as $k => $language) {
                        foreach ($block['trans'] as $field) {
                            $this->htmlFields['Trans'][$language->getCode()][$field['name']] = $this->_renderField($field, $language->getCode());
                        }
                    }
                }
            }
        }
    }

    private function _renderField($field, $language = false)
    {
        $field['value'] = $this->_newValueForField($field, $language);

        if ($field['type'] == 'image') {
            if (isset($field['options']['sizes'][0])) {
                $w = $field['options']['sizes'][0]['w'];
                $h = $field['options']['sizes'][0]['h'];
            } else {
                $w = $h = 0;
            }
            $field['url'] = ($field['value'] != '') ? $this->_urlPrefix($field) . $w . '-' . $h . '-' . $field['value'] : null;
        }

        if ($field['type'] == 'select') {
            if (isset($field['entity'])) {
                $field['list'] = $this->_selectEntity($field);
            } else {
                $method = 'getList' . ucfirst($field['name']);
                $field['list'] = $this->entityInfo->getEntity()->$method();
            }
        }

        $entityName = $this->entityInfo->getEntityName();
        $field['id'] = PttUtil::fieldId($entityName, $field['name'], $language);
        if ($field['type'] == 'image' || $field['type'] == 'file') {
            $field['check'] = PttUtil::fieldCheck($entityName, $field['name'], $language);
        }
        $field['name'] = PttUtil::fieldName($entityName, $field['name'], $language);



        $info = [
            'type' => $this->_getFieldType($field),
            'params' => $field
        ];

        if (isset($field['validations'])) {
            $info['validations'] = $field['validations'];
            unset($field['validations']);
        }
        return $this->twig->render('PttBundle:Form:factory.html.twig', $info);
    }

    private function _selectEntity($field)
    {
        $options = [];
        $entities = $this->container->get('pttServices')->getSimpleFilter($field['entity'], [
                'where' => (isset($field['options']['filterBy']) && is_array($field['options']['filterBy'])) ? $field['options']['filterBy'] : [],
                'orderBy' => (isset($field['options']['sortBy']) && is_array($field['options']['sortBy'])) ? $field['options']['sortBy'] : ['id' => 'asc']
        ]);

        $methodKey = (isset($field['identifier'])) ? 'get' . $field['identifier'] : 'getId';
        $methodValue = 'get' . $field['field'];
        foreach ($entities as $entity) {
            $options[$entity->$methodKey()] = $entity->$methodValue();
        }

        return $options;
    }

    private function _urlPrefix($field)
    {
        if (isset($field['options']['s3']) && $field['options']['s3']) {
            return PttUtil::pttConfiguration('s3')['prodUrl'] . PttUtil::pttConfiguration('s3')['dir'] . '/';
        } else {
            return (isset($field['options']['cdn']) && $field['options']['cdn']) ? PttUtil::pttConfiguration('cdn')['prodUrl'] : PttUtil::pttConfiguration('prefix') . PttUtil::pttConfiguration('images');
        }
    }

    private function _getExtraFields()
    {
        $field = [
        "name" => "id",
        "type" => "hidden",
        "disabled" => true,
        "options" => []
      ];

        $field['value'] = $this->_newValueForField($field);

        $info = [
          'type' => $this->_getFieldType($field),
          'params' => $field,
          "validations" => []
      ];

        return ['id' => $this->twig->render('PttBundle:Form:factory.html.twig', $info)];
    }

    private function _getFieldType($field)
    {
        switch ($field['type']) {
            case 'text':
            case 'disabled':
            case 'email':
            case 'hidden':
                return 'input';
                break;
            default:
                return $field['type'];
                break;
        }
    }

    private function _updateSentData()
    {
        $this->sentData = $this->request->request->all();
    }

    private function _performFieldsLoopAndCallMethodNamed($nameOfMethod)
    {
        $fields = $this->entityInfo->getFields();

        foreach ($fields['block'] as $key => $block) {
            if ($block['static']) {
                foreach ($block['static'] as $field) {
                    $this->$nameOfMethod($field);
                }
            }

            if ($this->languages && isset($block['trans'])) {
                foreach ($this->languages as $language) {
                    foreach ($block['trans'] as $field) {
                        $this->$nameOfMethod($field, $language->getCode());
                    }
                }
            }
        }
    }

    private function _validateField($field, $languageCode = false)
    {
        if (isset($field['validations'])) {
            foreach ($field['validations'] as $type => $message) {
                $validationClassName = PttClassNameGenerator::validation($type);
                $formValidation = new $validationClassName($this, $field, $languageCode);
                if (!$formValidation->isValid()) {
                    $this->errors->add($field['name'], $message, $languageCode);
                }
            }
        }

        $value = $this->_valueForField($field, $languageCode);

        $mapped = true;
        if (isset($field['validations']['mapped']) && $field['validations']['mapped'] == false) {
            $mapped = false;
        }

        if ($mapped) {
            $this->entityInfo->set($field['name'], $value, $languageCode);
        }
    }

    private function _valueForField($field, $languageCode = false)
    {
        $sentValueClassName = PttClassNameGenerator::sentValue($field['type']);
        $sentValue = new $sentValueClassName($field, $this, $languageCode);
        $value = $sentValue->value();

        return $value;
    }

    private function _saveForField($field, $languageCode = false)
    {
        $mapped = true;
        if (isset($field['validations']['mapped']) && $field['validations']['mapped'] == false) {
            $mapped = false;
        }

        if ($mapped && strtolower($field['type']) != 'entity' && strtolower($field['type']) != 'multipleentity' && strtolower($field['type']) != 'gallery') {
            $saveClassName = PttClassNameGenerator::save($field['type']);
            $formSave = new $saveClassName($field, $this->entityInfo, $this->request, $this->sentData, $this->container, $languageCode);
            $value = $formSave->value();
            if (strtolower($field['type']) == 'selectmultiple') {
                $this->entityInfo->set($field['name'] . '_model', $this->sentData[PttUtil::fieldName($this->formName, $field['name'] . '_model', $languageCode)]);
            }

            $this->entityInfo->set($field['name'], $value, $languageCode);
        }
    }

    private function _afterSaveForField($field, $languageCode = false)
    {
        $afterSaveClassName = PttClassNameGenerator::afterSave($field['type']);
        if ($afterSaveClassName) {
            $name =  (strtolower($field['type']) != 'selectmultiple') ? $field['type'] : $field['type'] . '_model';
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
