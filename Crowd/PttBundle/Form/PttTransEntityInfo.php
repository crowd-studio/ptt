<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Crowd\PttBundle\Util\PttUtil;

class PttTransEntityInfo
{
	private $entityInfo;
	private $transEntities;

	public function __construct(PttEntityInfo $pttEntityInfo, $languages = false)
	{
		$this->entityInfo = $pttEntityInfo;

		$this->_generateTransEntities($languages);
	}

	public function getTransEntities()
	{
		return $this->transEntities;
	}

	private function _generateTransEntities($languages)
	{
		$transClassName = $this->_transEntityClass();
		if ($languages && class_exists($transClassName)) {
			$this->transEntities = array();

			$this->transEntities = $this->_transEntity($languages);
		} else {
			$this->transEntities = false;
		}
	}

	private function _transEntity($languages)
	{		
		$transEntity = array();		
		foreach ($languages as $languageCode => $language) {
			$transEntity[$languageCode] = $this->_emptyTransEntity($languageCode);
		}	

		if ($this->entityInfo->get('id') != null) {
			$data = $this->entityInfo->getEntityManager()->getRepository($this->entityInfo->getRepositoryName() . 'Trans')->findBy(array('relatedId' => $this->entityInfo->get('id')));
			foreach ($data as $trans) {
				$transEntity[$trans->getLanguage()] = $trans;
			}
		}
		return $transEntity;
	}

	private function _transEntityClass()
	{
		return $this->entityInfo->getClassName() . 'Trans';
	}

	private function _emptyTransEntity($languageCode)
	{
		$transClassName = $this->_transEntityClass();
		$transEntity = new $transClassName();
		$transEntity->setRelatedId($this->entityInfo->get('id'));
		$transEntity->setLanguage($languageCode);
		return $transEntity;
	}
}