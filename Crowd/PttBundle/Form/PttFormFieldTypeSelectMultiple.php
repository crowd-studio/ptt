<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeSelectMultiple extends PttFormFieldType
{
	private $multiple;

	public function field()
	{

		$this->multiple = (isset($this->field->options['multiple']));
		$name = ($this->multiple) ? $this->field->getFormName($this->languageCode) . '[]' : false;

		$html = $this->start();
		$html .= $this->label();

		$method = 'get' . ucfirst($this->field->name) . '_model';
		$methodid = 'get' . ucfirst($this->field->name);
		$methodtitle = 'get' . ucfirst($this->field->name) . '_title';
		if ($this->languageCode){
			$model = $this->entityInfo->getTransEntities()[$this->languageCode]->$method();
			$id = $this->entityInfo->getTransEntities()[$this->languageCode]->$methodid();
			$title = $this->entityInfo->getTransEntities()[$this->languageCode]->$methodtitle();
		} else {
			$model = $this->entityInfo->getEntity()->$method();
			$id = $this->entityInfo->getEntity()->$methodid();
			$title = $this->entityInfo->getEntity()->$methodtitle();
		}

		$htmlField = '<select ';

		$formFieldId = $this->field->getFormId($this->languageCode);


		$htmlField .= 'id="' . $formFieldId . '_model" ';
		$htmlField .= 'name="' . trim($this->field->getFormName($this->languageCode), ']') . '_model]" ';

		$required = 'false';
		if(isset($this->field->options['required'])){
			$required = ($this->field->options['required']) ? 'true' : 'false';
		}

		$htmlField .= 'data-required="' . $required . '" ';

		if ($this->field->options['label'] != null) {
			$htmlField .= 'placeholder="' . $this->pttTrans->trans($this->field->options['label']) . '" ';
		}

		$htmlField .= 'limit="' . $this->pttTrans->trans($this->field->options['limit']) . '" ';

		$htmlField .= 'class="form-control select-multiple-model';

		if(count($this->field->options['entities']) == 1){
			$htmlField .= ' hidden';
		}

		$htmlField .= '">';

		$htmlField .= $this->_static($model);
		$htmlField .= '</select>';
		$htmlField .= '<select ';

		$htmlField .= 'id="' . $formFieldId . '" ';
		$htmlField .= 'name="' . $this->field->getFormName($this->languageCode) . '" ';

		$htmlField .= 'data-required="' . $required . '" ';

		if ($this->field->options['label'] != null) {
			$htmlField .= 'placeholder="' . $this->pttTrans->trans($this->field->options['label']) . '" ';
		}

		$htmlField .= 'class="form-control select-multiple-result"';

		$htmlField .= '>';

		$htmlField .= '<option value="-1">' . $this->field->options['empty'] . '</option>';

		if(count($this->field->options['entities']) == 1){
			$model = $this->field->options['entities'][0]['entity'];
		}

		if($model){
			$dql = 'select ptt from AdminBundle:' . $model . ' ptt';

	        $query = $this->em->createQuery($dql);
	        $query->setMaxResults($this->field->options['limit']);
	        $results = $query->getResult();

	        foreach ($results as $result) {
	        	
	        	if($id == $result->getId()){
	        		$selected = 'selected="selected"';
	        	} else {
	        		$selected = "";
	        	}
	        	$htmlField .= '<option '. $selected .' value="' . $result->getId() . '">' . $result->getTitle() . '</option>';	
	        }
		}
		
		$htmlField .= '</select>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraAttrsForField()
	{
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

	private function _static($model)
	{
		$html = '';
		if (isset($this->field->options['empty'])) {
			$html .= '<option value="-1">' . $this->pttTrans->trans($this->field->options['empty']) . '</option>';
		}
		if (isset($this->field->options)) {
			if(count($this->field->options['entities']) == 1){
				$html .= '<option selected="selected value="' . $this->field->options['entities'][0]['entity'] . '">' . $this->field->options['entities'][0]['label'] . '</option>';
			} else {
				foreach ($this->field->options['entities'] as $option) {
					$selected = ($option['entity'] == $model) ? ' selected="selected"' : '';
					$html .= '<option' . $selected . ' value="' . $option['entity'] . '">' . $option['label'] . '</option>';
				}
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
        return 'form-group select-multiple col-sm-' . $this->getWidth();
    }
}