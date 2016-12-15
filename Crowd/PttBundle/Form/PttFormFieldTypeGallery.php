<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeGallery extends PttFormFieldType
{
	protected $pttForm;

    public function __construct(PttForm $pttForm, PttField $field, $languageCode = false)
    {
        parent::__construct($pttForm, $field, $languageCode);
        $this->pttForm = $pttForm;
    }

	public function field()
	{
		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<div class="gallery-header"><div class="dropzone col-sm-12"></div><div class="col-sm-12 mg-btm-20 nopadding"><a class="btn btn-md btn-primary btn-collapse btn-danger" data-expand="'. $this->pttTrans->trans('expand') .'" data-collapse="'. $this->pttTrans->trans('collapse') .'">' . $this->pttTrans->trans('expand') . '</a>';
        $htmlField .= '<a class="btn btn-md btn-primary btn-sort" data-order="' . $this->pttTrans->trans('order') . '" data-edit="' . $this->pttTrans->trans('edit') . '">' . $this->pttTrans->trans('order') . '</a>';
        $htmlField .= '</div></div><div class="related-multiple-entities">';

        $htmlField .= '<ul class="multi-sortable"><li class="head"><span class="handle">Order</span><span class="hidden-xs">Entity</span><span class="actions"></span></li>';
        $htmlField .= $this->_hiddenDiv();
        $htmlField .= $this->_fillData();
        $htmlField  .= '</ul></div>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	private function _hiddenDiv() {

        $pttHelper = new PttHelperFormFieldTypeGallery($this->entityInfo, $this->field, $this->container, $this->em);
        $form = $pttHelper->formForEntity($pttHelper->cleanRelatedEntity());

        $formName = $this->field->options['label'];

        
        $htmlField = '<script type="text/template" class="template"><div class="collapse-head"><span class="handle hidden"></span><span class="title-triangle"><a class="triangle-open triangle"></a><a class="title title-open">'. $formName .' {{index}}</a></span><a class="remove list-eliminar"></a></div><div class="collapse-body">' . $form->createView('multi');
        $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-{{index}}-_order" name="'. $this->field->getFormName() . '[{{index}}]' .'[_order]" data-required="false" class="form-control field-order" value="{{index}}">';
        $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-{{index}}-_model" name="'. $this->field->getFormName() . '[{{index}}]' .'[_model]" data-required="false" class="form-control" value="'. $this->pttForm->getEntityInfo()->getEntityName() .'">';
        $htmlField .= '</div></script>';

        $formErrors = $this->pttForm->getErrors($this->field->name);

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

    private function _fillData()
    {
        $htmlField = '';
        if (is_array($this->value) && count($this->value)){
            $pttHelper = new PttHelperFormFieldTypeEntity($this->entityInfo, $this->field, $this->container, $this->em);
            $formName = $this->field->options['label'];


            if (is_array($this->value) && count($this->value)) {
                $index = 1;
                foreach ($this->value as $key => $entityData) {
                    if ($key != -1) {
                        $errors = (isset($formErrors[$key])) ? $formErrors[$key] : false;
                        $form = $pttHelper->formForEntity($pttHelper->entityWithData($entityData), $index, $errors);
                        $htmlField .= '<li class="entity" draggable="true"><div class="collapse-head"><span class="handle hidden"></span><span class="title-triangle"><a class="triangle-closed triangle"></a><a class="title title-closed">'. $formName .' '. $index .'</a></span><a class="remove list-eliminar"></a></div><div class="collapse-body hidden">' . $form->createView('multi');
                        $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-' . $index .'-_order" name="'. $this->field->getFormName() . '[' . $index . ']' .'[_order]" data-required="false" class="form-control field-order" value="'. $index .'">';
                        $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-' . $index .'-_model" name="'. $this->field->getFormName() . '[' . $index . ']' .'[_model]" data-required="false" class="form-control" value="'. $this->pttForm->getEntityInfo()->getEntityName() .'">';
                        $htmlField .= '</div></li>';
                        $index++;
                    }
                }
            }
        }
        return $htmlField;
    }

	protected function extraClassesForFieldContainer()
    {
        return 'form-group entity col-sm-12';
    }

    protected function extraAttrsForContainer()
    {
        $attrs = ['data-prefix' => PttUtil::pttConfiguration('prefix')];
        return $attrs;
    }

}
