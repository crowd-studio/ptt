<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;
use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeDisabled extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();
		$html .= $this->label();

		$language = PttUtil::pttConfiguration('preferredLanguage');
		if ($language == false) {
			$language = 'en';
		}

		$htmlField = '<input type="text" data-language="' . $language . '" ';
		$htmlField .= $this->attributes();

		if ($this->entityInfo->getEntity()->getPttId() || !isset($this->field->options['editable']) || !$this->field->options['editable']){
			
			if(isset($this->field->options['entity'])){
				if(is_object($this->value)){
					$method = 'get' . ucfirst($this->field->options['column']);
					$value = (method_exists($this->value, $method)) ? $this->value->$method() : '';
				} else {
					// Una entitat diferent
					$repository = $this->container->get('pttEntityMetadata')->respositoryName($this->field->options['entity']);
					$entity = $this->em->getRepository($repository)->findById($this->value);

					$method = 'get' . ucfirst($this->field->options['column']);
					$value = (method_exists($entity, $method)) ? $entity->$method() : '';
				}
				
				
			} elseif(isset($this->field->options['column'])){
				// De l'entitat una columna
				$method = 'get' . ucfirst($this->field->options['column']);
				$entity = $this->entityInfo->getEntity();
				$value = (method_exists($entity, $method)) ? $entity->$method() : '';
			} else {
				// De l'entitat la mateixa columna
				$value = $this->value;
			}

			$htmlField .= 'value="' . $value . '"';
			$htmlField .= ' disabled >';
		} else {
			$htmlField .= ' >';
		}

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group text col-sm-12';
    }
}