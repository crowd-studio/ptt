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

	private $em;
	private $securityContext;
	private $container;
	private $entityInfo;
	private $sentData;
	private $sentDataTrans;
	private $errors;
	private $request;
	private $languages;
	private $htmlFields;
	private $pttTrans;
	private $totalData = 0;

	public function __construct(EntityManager $entityManager, TokenStorage $securityContext, ContainerInterface $serviceContainer)
	{

		$this->em = $entityManager;
		$this->securityContext = $securityContext;
		$this->container = $serviceContainer;
		$this->languages = PttUtil::pttConfiguration('languages', false);
		$this->htmlFields = array();
	}

	public function setRequest($requestObj)
    {
    	if (is_a($requestObj, 'Symfony\Component\HttpFoundation\RequestStack')) {
    		$this->request = $requestObj->getCurrentRequest();
    	} else if (is_a($requestObj, 'Symfony\Component\HttpFoundation\Request')) {
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
		$this->entityInfo = new PttEntityInfo($entity, $this->em, $this->container, $this->languages, $this->pttTrans);
	}

	public function setFormName($formName)
	{
		$this->entityInfo->setFormName($formName);
	}

	public function getEntityInfo()
	{
		return $this->entityInfo;
	}

	public function getEntityManager()
	{
		return $this->em;
	}

	public function getSentData($fieldName = false, $languageCode = false)
	{

		if ($fieldName != false) {
			if ($languageCode) {
				if(isset($this->sentData['Trans'][$languageCode][$fieldName])){
					return $this->sentData['Trans'][$languageCode][$fieldName];
				} else if (isset($this->sentDataTrans['Trans'][$languageCode][$fieldName])) {
					return $this->sentDataTrans['Trans'][$languageCode][$fieldName];
				} else {
					return null;
				}
			} else {
				return (isset($this->sentData[$fieldName])) ? $this->sentData[$fieldName] : null;
			}
		} else if ($languageCode) {
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
		if ($key != false && $key != 'multi') {
			$html = $this->_createSingleView($key);
		} else {
			$html = $this->_createGlobalView($key);
		}
		
		return $html;
	}

	public function isValid()
	{
		$this->_updateSentData();
		$this->_performFieldsLoopAndCallMethodNamed('_validateField');

		return !$this->errors->hasErrors();
	}

	public function save()
	{
		var_dump($this->entityInfo->getTransEntities());die();
		$this->_performFieldsLoopAndCallMethodNamed('_saveForField');	

		if ($this->entityInfo->hasMethod('setTitle') && $this->entityInfo->hasMethod('getTitle')) {
			if(isset($this->sentData['Trans'][PttUtil::pttConfiguration('preferredLanguage', false)]['title'])){
				$this->entityInfo->set('title', $this->getSentData('title', PttUtil::pttConfiguration('preferredLanguage', false)));
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
		
		if(!$entityPrincipal->getPttId()){
			if(method_exists($entityPrincipal, 'set_Order')){
				if(!$entityPrincipal->get_Order()){
					$entityPrincipal->set_Order(-1);
				}
			}
		}


		if($this->entityInfo->hasMethod('getTrans')){
			if(!count($this->entityInfo->get('trans'))){

			}
		}

		$this->em->persist($entityPrincipal);
		$this->em->flush();

		$fileArrPrinc = explode('\\', get_class($entityPrincipal));
        $filenamePrinc = end($fileArrPrinc);

		if ($this->entityInfo->getTransEntities()) {
			foreach ($this->entityInfo->getTransEntities() as $entity) {
				$fileArr = explode('\\', get_class($entity));
            	$filename = end($fileArr);
				if($filename === $filenamePrinc . 'Trans' ){	
            		if(method_exists($entity, 'getTitle')){
						$entity->setSlug(PttUtil::slugify($entity->getTitle()));	
					} else {
						$entity->setSlug('');
					}	
					foreach ($this->sentData['Trans'][$entity->getLanguage()] as $key => $value) {
			            if ($key != 'id') {
			                $methodName = 'set' . ucfirst($key);
			                if (method_exists($entity, $methodName)) {
			                    $entity->{$methodName}($value);
			                }
			            }
			        }
					$entity->setRelatedId($entityPrincipal->getPttId());
					$this->em->persist($entity);
            	}
			}
		}
		
		$this->em->flush();

		$this->_performFieldsLoopAndCallMethodNamed('_afterSaveForField');
	}

	//PRIVATE

	private function _createSingleView($key)
	{
		return (isset($this->htmlFields[$key])) ? $this->htmlFields[$key] : 'Input ' . $key . ' not found';
	}

	private function _createGlobalView($key)
	{
		$html = '';
		$entityName = $this->entityInfo->getEntityName();
		$fields = $this->entityInfo->getFields();

		$index = 0;
		foreach ($fields->block as $block) {

			if ($key == false){
				$html .= '<div class="block-container col-sm-12"><div class="block-header"><p>' . $block . '</p></div><div class="block-body col-sm-12">';
			} else {
				$html .= '<div><div>';
			}
			
			if($fields->static[$index]){
				foreach ($fields->static[$index] as $field) {
					$html .= $this->htmlFields[$field->name];
				}
			}
			

			if ($this->languages && isset($fields->trans[$index]) && $fields->trans[$index]) {
				$html .= '<ul class="nav nav-tabs col-sm-12">';
				$i = 0;
				foreach ($this->languages as $languageCode => $languageTitle) {
					$active = ($i == 0) ? 'active' : '';
					$error = ($this->errors->hasErrors($languageCode)) ? ' error' : '';
					$html .= '<li class="' . $active . $error . ' language-'. $languageCode .'"><a href="language-' . $languageCode . '" >' . $languageTitle . '</a></li>';
					$i++;
				}
				$html .= '</ul><div class="tab-content col-sm-12">';
				$i = 0;
				foreach ($this->languages as $languageCode => $languageTitle) {
					$active = ($i == 0) ? ' active' : '';
					$html .= '<div class="tab-pane' . $active . ' language-' . $languageCode  . '">';
					foreach ($fields->trans[$index] as $field) {
						$html .= $this->htmlFields[$languageCode][$field->name];
					}
					$html .= '</div>';
					$i++;
				}
				$html .= '</div>';
			}
			$html .= '</div></div>';
			$index++;
		}
		

		return $html;
	}

	private function _makeHtmlFields()
	{
		if (!count($this->htmlFields)) {
			$fields = $this->entityInfo->getFields();

			$index = 0;
			foreach ($fields->block as $block) {
				if($fields->static[$index]){
					foreach ($fields->static[$index] as $field) {
						$fieldClassName = PttClassNameGenerator::field($field->type);
						$formField = new $fieldClassName($this, $field);
						$this->htmlFields[$field->name] = $formField->field();
					}
				}
				if ($this->languages && $fields->trans) {

					foreach ($this->languages as $languageCode => $languageTitle) {
							if($fields->trans[$index]){
								foreach ($fields->trans[$index] as $field) {
								if (strpos($field->getFormNameSec(), '[Trans]') === false){
									$field->setFormName($field->getFormNameSec() . '[Trans]');
								}

								$fieldClassName = PttClassNameGenerator::field($field->type);
								$formField = new $fieldClassName($this, $field, $languageCode);
								if (!isset($this->htmlFields[$languageCode]) || !is_array($this->htmlFields[$languageCode])) {
									$this->htmlFields[$languageCode] = array();
								}
								$this->htmlFields[$languageCode][$field->name] = $formField->field();
							}
						}
					}
				}
				$index++;
			}
			
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
				foreach ($this->languages as $languageCode => $languageTitle) {
					$entityTrans = $this->entityInfo->getFormName() . '[Trans]';
					$aux = $this->request->get($entityTrans);
					if(isset($aux)){
						$transEntity[$languageCode] = reset($aux);
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
		$index = 0;

		foreach ($fields->block as $block) {
			if($fields->static[$index]){
				foreach ($fields->static[$index] as $field) {
					$this->$nameOfMethod($field);
				}
			}
			
			if ($this->languages && isset($fields->trans[$index])) {
				foreach ($this->languages as $languageCode => $languageTitle) {
					if($fields->trans[$index]){
						foreach ($fields->trans[$index] as $field) {
							$this->$nameOfMethod($field, $languageCode);
						}
					}
				}
			}
			$index++;
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
	}

	private function _saveForField(PttField $field, $languageCode = false)
	{
		$fieldClassName = PttClassNameGenerator::field($field->type);
		$saveClassName = PttClassNameGenerator::save($field->type);
		$formSave = new $saveClassName($field, $this->entityInfo, $this->request, $this->sentData, $this->container, $languageCode);
		$value = $formSave->value();
		
		if (strpos($fieldClassName, 'PttFormFieldTypeSelectMultiple') !== false) {
			 $this->entityInfo->set($field->name . '_model', $this->sentData[$field->name . '_model'], $languageCode);
		}

		if($value){
			$this->entityInfo->set($field->name, $value, $languageCode);
		} else {
			$value = $this->_valueForField($field, $languageCode);
			$this->entityInfo->set($field->name, $value, $languageCode);
		}
	}

	private function _valueForField(PttField $field, $languageCode = false)
	{
		$sentValueClassName = PttClassNameGenerator::sentValue($field->type);
		$sentValue = new $sentValueClassName($field, $this, $languageCode);
		$value = $sentValue->value();
		return $value;
	}

	private function _afterSaveForField(PttField $field, $languageCode = false)
	{
		$afterSaveClassName = PttClassNameGenerator::afterSave($field->type);
		if ($afterSaveClassName) {
			$fieldClassName = PttClassNameGenerator::field($field->type);
			$formField = new $fieldClassName($this, $field);

			if (strpos($fieldClassName, 'PttFormFieldTypeSelectMultiple') === false) {
				$afterFormSave = new $afterSaveClassName($field, $this->entityInfo, $this->getSentData($field->name, $languageCode), $this->container, $languageCode);
			} else {
				$afterFormSave = new $afterSaveClassName($field, $this->entityInfo, $this->getSentData($field->name . '_model', $languageCode), $this->container, $languageCode);
			}
			
			$afterFormSave->perform();
		}
	}

	// public function setTotalData ($totalData){
	// 	$this->totalData = $totalData;
	// }
}