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
	private $search;

	public function field()
	{
		$this->search = (isset($this->field->options['search']) && $this->field->options['search']);
		if ($this->search){
			$this->field->options['attr'] = [];
			$this->field->options['attr']['class'] = 'select-search';
		}

		$this->multiple = (isset($this->field->options['multiple']));
		$name = ($this->multiple) ? $this->field->getFormName($this->languageCode) . '[]' : false;

		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<select ';
		$htmlField .= $this->attributes(false, $name);
		if ($this->search){
			$this->field->options['filterBy'] = ['id' => $this->value];
			$entity = $this->_entities();
			if ($this->value > 0) {
				$htmlField .= ' data-title="' . $entity[0]->getTitle() . '"';
			}

			$htmlField .= ' value="' . $this->value . '"';
		}
		$htmlField .= '>';

		if (!$this->search){
			$type = '_' . $this->field->options['type'];
			if (method_exists($this, $type)) {
				$htmlField .= $this->{$type}();
			}
		}

		$htmlField .= '</select>';

		if($this->search){
			$htmlField .= '<p class="help-block">Type whatever you want to search in database</p>';
		}

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraAttrsForField()
	{
		if ($this->search){
			return ['data-model' => $this->field->options['entity']];
		} elseif ($this->multiple) {
			return ['multiple' => 'multiple', 'title' => (isset($this->field->options['empty'])) ? $this->field->options['empty'] : ''];
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
			$methodName = 'get' . ucfirst($this->field->name);
			$existEntity = $this->entityInfo->getEntity()->$methodName();

			foreach ($existEntity as $key => $exist) {
				if($id == $exist->getPttId()){
					return ' selected="selected"';
				}
			}
			return '';
			return ($existEntity->contains($id)) ? ' selected="selected"' : '';
		} elseif ($this->field->options['type'] == 'entity') {
			return ($this->value !== null && $id == $this->value->getPttId()) ? ' selected="selected"' : '';
		} else {
			return ($id == $this->value) ? ' selected="selected"' : '';
		}
	}

	private function _entities()
	{
		return $this->container->get('pttServices')->getSimpleFilter($this->field->options['entity'], [
				'where' => (isset($this->field->options['filterBy']) && is_array($this->field->options['filterBy'])) ? $this->field->options['filterBy'] : [],
				'orderBy' => (isset($this->field->options['sortBy']) && is_array($this->field->options['sortBy'])) ? $this->field->options['sortBy'] : ['id' => 'asc']
		]);
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group select col-sm-' . $this->getWidth();
    }
}
