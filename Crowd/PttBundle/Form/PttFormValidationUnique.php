<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormValidationUnique extends PttFormValidation
{
	private $em;

	public function __construct(PttForm $pttForm, $entity)
	{
		parent::__construct($pttForm, $entity);

		$this->em = $pttForm->getEntityManager();
	}

	public function isValid()
	{
		$dql = 'select count(e) from ' . $this->entityInfo->getRepositoryName() . ' e where e.id != :id and e.' . $this->field->name . ' = :' . $this->field->name;

		$id = $this->entityInfo->get('id');
		if ($id == null) {
			$id = -1;
		}

		$query = $this->em->createQuery($dql)
				 ->setParameter('id', $id)
				 ->setParameter($this->field->name, $this->_sentValue());

		$count = $query->getSingleScalarResult();

		return ($count == 0);
	}
}