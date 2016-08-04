<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeSelect extends PttFormFieldType
{
	private $multiple;

	public function field()
	{
		if (isset($this->field->options['search']) && $this->field->options['search']){
			$this->field->options['attr'] = [];
			$this->field->options['attr']['class'] = 'select-search';
		}

		$this->multiple = (isset($this->field->options['multiple']));
		$name = ($this->multiple) ? $this->field->getFormName($this->languageCode) . '[]' : false;

		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<select ';
		$htmlField .= $this->attributes(false, $name);
		$htmlField .= '>';

		if (!isset($this->field->options['search']) || !$this->field->options['search']){
			$type = '_' . $this->field->options['type'];
			if (method_exists($this, $type)) {
				$htmlField .= $this->{$type}();
			}
		}

		$htmlField .= '</select>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraAttrsForField()
	{
		if (isset($this->field->options['search']) && $this->field->options['search']){
			return array('data-model' => $this->field->options['entity']);
		}

		if ($this->multiple) {
			$label = (isset($this->field->options['empty'])) ? $this->field->options['empty'] : false;
			if ($label) {
				$label = (isset($this->field->options['label'])) ? $this->field->options['label'] : $this->field->name;
			}
			return array('multiple' => 'multiple', 'title' => $this->pttTrans->trans($label));
		} else {
			return parent::extraAttrsForField();
		}
	}

	private function _static()
	{
		$html = '';
		if (isset($this->field->options['empty'])) {
			if(isset($this->field->validations['not_blank'])){
				$value = "";
			} else {
				$value = "-1";
			}
			$html .= '<option value="'. $value .'">' . $this->pttTrans->trans($this->field->options['empty']) . '</option>';
		}
		if (isset($this->field->options)) {
			foreach ($this->field->options['options'] as $key => $value) {
				$selected = ($key == $this->value) ? ' selected="selected"' : '';
				$html .= '<option' . $selected . ' value="' . $key . '">' . $value . '</option>';
			}
		}
		return $html;
	}

	private function _entity()
	{
		$html = '';
		if (isset($this->field->options['empty']) && !$this->multiple) {
			$html .= '<option value="-1">' . $this->pttTrans->trans($this->field->options['empty']) . '</option>';
		}
		if (isset($this->field->options['entity'])) {
			$entities = $this->_entities();
			foreach ($entities as $entity) {
				$extraDatas = (isset($this->field->options['extraDatas'])) ? $this->field->options['extraDatas'] : false;
				$extraHtmlArr = false;
				if ($extraDatas) {
					$extraHtmlArr = array();
					foreach ($extraDatas as $key => $methodName) {
						if (method_exists($entity, $methodName)) {
							$extraHtmlArr[] = $key . '="' . $entity->{$methodName}() . '"';
						}
					}
				}
				$html .= '<option' . $this->_selected($entity->getPttId());
				if ($extraHtmlArr) {
					$html .= ' ' . implode(' ', $extraHtmlArr);
				}
				$html .= ' value="' . $entity->getPttId() . '">' . $entity . '</option>';
			}
		}
		return $html;
	}

	private function _selected($id)
	{
		if ($this->multiple) {
			return (in_array($id, $this->value)) ? ' selected="selected"' : '';
		} else {
			return ($id == $this->value) ? ' selected="selected"' : '';
		}
	}

	private function _entities()
	{
		$sortBy = (isset($this->field->options['sortBy']) && is_array($this->field->options['sortBy'])) ? $this->field->options['sortBy'] : array('id' => 'asc');
		$filterBy = (isset($this->field->options['filterBy']) && is_array($this->field->options['filterBy'])) ? $this->field->options['filterBy'] : array();

		$entities = $this->em->getRepository($this->container->get('pttEntityMetadata')->respositoryName($this->field->options['entity']))->findBy($filterBy, $sortBy);
		return $entities;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group select col-sm-12';
    }
}