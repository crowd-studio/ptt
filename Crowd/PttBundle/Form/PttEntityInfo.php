<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Crowd\PttBundle\Util\PttUtil;

class PttEntityInfo
{
	private $entity;
	private $transEntities;
	private $entityName;
	private $bundle;
	private $repositoryName;
	private $className;
	private $fields;
	private $em;
	private $formName;
	private $container;
	private $pttTrans;

	public function __construct($entity, EntityManager $entityManager, ContainerInterface $container, $languages = false, $pttTrans)
	{
		$this->container = $container;

		$this->className = $this->container->get('pttEntityMetadata')->className($entity);

		$this->entityName = $this->container->get('pttEntityMetadata')->entityName($entity);

		$this->bundle = $this->container->get('pttEntityMetadata')->bundle($entity);

		$this->repositoryName = $this->container->get('pttEntityMetadata')->respositoryName($entity);

		$this->em = $entityManager;

		$this->entity = $entity;

		$this->formName = $this->entityName;

		$pttTransEntityInfo = new PttTransEntityInfo($this, $languages);
		$this->transEntities = $pttTransEntityInfo->getTransEntities();

		$this->pttTrans = $pttTrans;
		
		$this->fields = false;

		$this->_fetchFields();
	}

	public function setFormName($formName)
	{
		$this->formName = $formName;
		$this->_fetchFields();
	}

	public function getFormName()
	{
		return $this->formName;
	}

	public function getEntity()
	{
		return $this->entity;
	}

	public function getTransEntities()
	{
		return $this->transEntities;
	}

	public function getEntityName()
	{
		return $this->entityName;
	}

	public function getRepositoryName()
	{
		return $this->repositoryName;
	}

	public function getClassName()
	{
		return $this->className;
	}

	public function getBundle()
	{
		return $this->bundle;
	}

	public function getFields($property = false)
	{
		if ($property != false) {
			return $this->fields->{$property};
		}
		return $this->fields;
	}

	public function getEntityManager()
	{
		return $this->em;
	}

	public function hasMethod($methodName, $languageCode = false)
	{
		if ($languageCode) {
			$entity = $this->_entityForLanguageCode($languageCode);
			return method_exists($entity, $methodName);
		} else {
			return method_exists($this->entity, $methodName);
		}
	}

	public function set($name, $value, $languageCode = false)
	{
		if ($name != 'id') {
			$methodName = 'set' . ucfirst($name);
			if ($languageCode) {
				if (!$this->hasMethod($methodName, $languageCode)) {
					throw new \Exception('The method ' . $methodName . ' does not exist for trans entity ' . $this->getEntityName());
				} else {
					$entity = $this->_entityForLanguageCode($languageCode);
					$entity->{$methodName}($value);
				}
			} elseif(is_array($value)) {

				$methodName = 'update' . ucfirst($name);
				if (!$this->hasMethod($methodName)) {
					throw new \Exception('The method ' . $methodName . ' does not exist for entity ' . $this->getEntityName());
				} else {
					$this->entity->$methodName($value);
				}
			} elseif (is_object($value)) {
				$methodName = 'set' . ucfirst($name);
				if (!$this->hasMethod($methodName)) {
					throw new \Exception('The method ' . $methodName . ' does not exist for entity ' . $this->getEntityName());
				} else {
					$this->entity->$methodName($value);
				}
			} else {
				if (!$this->hasMethod($methodName)) {
					throw new \Exception('The method ' . $methodName . ' does not exist for entity ' . $this->getEntityName());
				} else {
					$this->entity->{$methodName}($value);
				}
			}
		}
	}

	public function get($name, $languageCode = false)
	{
		$methodName = 'get' . ucfirst($name);

		if ($languageCode) {
			if (!$this->hasMethod($methodName, $languageCode)) {
				throw new \Exception('The method ' . $methodName . ' does not exist for trans entity ' . $this->getEntityName());
			} else {
				$entity = $this->_entityForLanguageCode($languageCode);
				return $entity->{$methodName}();
			}
		} else {
			if (!$this->hasMethod($methodName)) {
				throw new \Exception('The method ' . $methodName . ' does not exist for entity ' . $this->getEntityName());
			} else {
				return $this->entity->{$methodName}();
			}
		}
	}

	public function appendField($field)
	{
		$this->fields->addField($this->formName, $field);
	}

	private function _entityForLanguageCode($languageCode)
	{
		return $this->transEntities[$languageCode];
	}

	private function _fetchFields()
	{
		$kernel = $this->container->get('kernel');
		$filePath = $kernel->locateResource('@' . $this->bundle . '/Form/' . $this->entityName . '.yml');
		$this->fields = new PttFields($filePath, $this->entity, $this->entityName, $this->formName, $this->pttTrans);
	}
}