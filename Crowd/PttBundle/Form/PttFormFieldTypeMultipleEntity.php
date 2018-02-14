<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeMultipleEntity extends PttFormFieldType
{
	public function field() {
		$name = false;

		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<div class="multi-selector-container"><div class="col-sm-6 nopadding selector-container"><select style="display:none" class="multi-selector form-control" ';
		$htmlField .= $this->attributes(false, $name);
		$htmlField .= ' data-selector>';

		$htmlField .= $this->_options();

		$htmlField .= '</select><a class="btn btn-md btn-primary add-multi">' . $this->pttTrans->trans('add') . '</a></div>';
		$htmlField .= '<div class="col-sm-6 nopadding"><a class="btn btn-md btn-primary btn-collapse btn-danger" data-expand="'. $this->pttTrans->trans('expand') .'" data-collapse="'. $this->pttTrans->trans('collapse') .'">' . $this->pttTrans->trans('expand') . '</a>';
		$htmlField .= '<a class="btn btn-md btn-primary btn-sort" data-order="' . $this->pttTrans->trans('order') . '" data-edit="' . $this->pttTrans->trans('edit') . '">' . $this->pttTrans->trans('order') . '</a>';
		$htmlField .= '</div></div><div class="related-multiple-entities"><ul class="multi-sortable"><li class="head"><span class="handle">Order</span><span class="hidden-xs">Entity</span><span class="actions"></span></li>';

		$htmlField .= $this->_hiddenDiv();
		$htmlField .= $this->_fillData();

		$htmlField 	.= '</ul></div>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	private function _hiddenDiv() {
		$htmlField = '';
		$sortableList = '';
		if (isset($this->field->options['modules'])) {
			foreach ($this->field->options['modules'] as $key => $value) {
				$pttHelper = new PttHelperFormFieldTypeMultipleEntity($this->entityInfo, $this->field, $this->container, $this->em, $value['entity'], $value['label']);
        		$form = $pttHelper->formForEntity($pttHelper->cleanRelatedEntity());

                $htmlField .= '<script type="text/template" class="template" data-type="' . $value['entity'] . '"><div class="collapse-head"><span class="handle hidden"></span><span class="title-triangle"><a class="triangle-open triangle"></a><a class="title title-open">'. $value['label'] .'</a></span><a class="remove list-eliminar"></a></div><div class="collapse-body">' . $form->createView('multi');
                $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-{{index}}-type" name="'. $this->field->getFormName() . '[{{index}}]' .'[type]" data-required="false" class="form-control" value="'. $value['entity'] .'">';
                $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-{{index}}-_order" name="'. $this->field->getFormName() . '[{{index}}]' .'[_order]" data-required="false" class="form-control field-order" value="{{index}}">';
                $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-{{index}}-_model" name="'. $this->field->getFormName() . '[{{index}}]' .'[_model]" data-required="false" class="form-control" value="'. $this->pttForm->getEntityInfo()->getEntityName() .'"> </div></script>';
				$formErrors = $this->pttForm->getErrors($this->field->name);
			}
		}

		return $htmlField;
	}

    function moduleSort( $a, $b ) {
        if (!isset($a)) {
            return -1;
        } elseif (!isset($b)) {
            return 1;
        } else {
            return $a->get_Order() == $b->get_Order() ? 0 : ( $a->get_Order() > $b->get_Order() ) ? 1 : -1;
        }
    }

	private function _fillData() {
        $htmlField = '';
		if (is_array($this->value) && count($this->value))
        {
            $index = 1;

            if(isset($this->value[0]) && is_array($this->value[0])){
            	 $data = array();
	            foreach ($this->value as $key => $entityData) {
	                $data = array_merge($data, $entityData);
	            }
            } else {
            	$data = $this->value;
            }
            usort($data, array($this, "moduleSort"));

            $size = count($data);
            $moduleTitles = array();
            foreach($this->field->options['modules'] as $key => $value){
            	$moduleTitles[$value['entity']] = $value['label'];
            }

            if ($key != -1 && $size > 0) {
                for($i=0; $i<$size; $i++) {
                	if(!is_array($data[$i])){
						$class = explode('\\', (string)get_class($data[$i]));
                		$entity = array_pop($class);
                	} else {
                		$entity = $data[$i]['type'];

                	}

                    $pttHelper = new PttHelperFormFieldTypeMultipleEntity($this->entityInfo, $this->field, $this->container, $this->em, $entity);
                    $errors = (isset($formErrors[$key])) ? $formErrors[$key] : false;
                    $form = $pttHelper->formForEntity($pttHelper->entityWithData($data[$i]), $index, $errors);
                    $formName = $moduleTitles[$form->getEntityInfo()->getEntityName()];

                    $field = array(
			            'name' => '_type',
			            'type' => 'hidden',
			            'options' => array()
			        );
                    $htmlField .= '<li class="entity"><div class="collapse-head"><span class="handle hidden"></span><span class="title-triangle"><a class="triangle-closed triangle"></a><a class="title title-closed">'. $formName .'</a></span><a class="remove list-eliminar"></a></div>';
                    $htmlField .= '<div class="collapse-body hidden">' . $form->createView('multi') . '<input type="hidden" id="'. $this->field->getFormName() . '-' . $index .'-type" name="'. $this->field->getFormName() . '[' . $index . ']' .'[type]" data-required="false" class="form-control" value="'. $entity .'">';
                    $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-' . $index .'-_order" name="'. $this->field->getFormName() . '[' . $index . ']' .'[_order]" data-required="false" class="form-control field-order" value="'. $index .'">';
                    $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-' . $index .'-_model" name="'. $this->field->getFormName() . '[' . $index . ']' .'[_model]" data-required="false" class="form-control" value="'. $this->pttForm->getEntityInfo()->getEntityName() .'"></div></li>';

                    $index++;
                }
            }
        }

		return $htmlField;
	}

	private function _options()
	{
		$html = '';
		if (isset($this->field->options['empty'])) {
			$html .= '<option value="-1">' . $this->pttTrans->trans($this->field->options['empty']) . '</option>';
		}
		if (isset($this->field->options['modules'])) {
			foreach ($this->field->options['modules'] as $key => $value) {
				$html .= '<option value="' . $value['entity'] . '">' . $value['label'] . '</option>';
			}
		}
		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group multipleentity col-sm-' . $this->getWidth();
    }
}
