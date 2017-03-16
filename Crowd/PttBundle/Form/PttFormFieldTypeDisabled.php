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


		$htmlField = '<input type="text" ';
		$htmlField .= $this->attributes();

		if ($this->entityInfo->getEntity()->getPttId() || !isset($this->field->options['editable']) || !$this->field->options['editable']){

			if(isset($this->field->options['entity'])){
				if(is_object($this->value)){
					$method = 'get' . ucfirst($this->field->options['column']);
					$value = (method_exists($this->value, $method)) ? $this->value->$method() : '';
				} else {
					// Una entitat diferent
					$entity = $this->container->get('pttServices')->getOne($this->field->options['entity'], $this->value);

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


			if(is_object($value) && is_a($value, 'DateTime')){
				$value = $value->format('d/m/Y H:i:s');
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
        return 'form-group text col-sm-' . $this->getWidth();
    }
}
