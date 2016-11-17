<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeEntity extends PttFormFieldType
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

        $htmlField = '<div class="multi-selector-container"><div class="col-sm-6 nopadding"><a class="btn btn-md btn-primary add">' . $this->pttTrans->trans('add') . '</a></div>';
        $htmlField .= '<div class="col-sm-6 nopadding"><a class="btn btn-md btn-primary btn-collapse btn-danger" data-expand="'. $this->pttTrans->trans('expand') .'" data-collapse="'. $this->pttTrans->trans('collapse') .'">' . $this->pttTrans->trans('expand') . '</a>';
        $htmlField .= '<a class="btn btn-md btn-primary btn-sort" data-order="' . $this->pttTrans->trans('order') . '" data-edit="' . $this->pttTrans->trans('edit') . '">' . $this->pttTrans->trans('order') . '</a>';
        $htmlField .= '</div></div><div class="related-multiple-entities">';

        $htmlField .= '<ul class="multi-sortable"><li class="head"><span class="handle">Order</span><span class="hidden-xs">Entity</span><span class="actions">'. $this->pttTrans->trans('actions') .'</span></li>';
        $htmlField .= $this->_hiddenDiv();
        $htmlField .= $this->_fillData();
        $htmlField  .= '</ul></div>';

        $html .= $htmlField;
        $html .= $this->end();

        return $html;
    }

    private function _hiddenDiv() {
        $pttHelper = new PttHelperFormFieldTypeEntity($this->entityInfo, $this->field, $this->container, $this->em);
        $htmlField = '<script type="text/template" class="template">'. $this->_getHtml('{{index}}', $this->field->options['label'], $pttHelper->formForEntity($pttHelper->cleanRelatedEntity())) .'</script>';

        return $htmlField;
    }

    private function _fillData()
    {
        $htmlField = '';
        if($this->value){
            $pttHelper = new PttHelperFormFieldTypeEntity($this->entityInfo, $this->field, $this->container, $this->em);
            for ($i=0; $i < count($this->value); $i++) { 
                $ent = $this->value->get($i);
                $htmlField .= '<li class="entity">' . $this->_getHtml($i+1, $this->field->options['label'], $pttHelper->formForEntity($ent, $i+1, (isset($formErrors[$i])) ? $formErrors[$i] : false)) . '</li>';
            }
        }
        
        return $htmlField;
    }

    private function _getHtml($key, $formName, $form){
        $htmlField = '<div class="collapse-head"><span class="handle hidden"></span><span class="title-triangle"><a class="triangle-closed triangle"></a><a class="title title-closed">'. $formName .' '. $key .'</a></span><a class="remove list-eliminar">'.$this->pttTrans->trans('remove').'</a></div><div class="collapse-body hidden">' . $form->createView('multi');
        $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-' . $key .'-_order" name="'. $this->field->getFormName() . '[' . $key . ']' .'[_order]" data-required="false" class="form-control field-order" value="'. $key .'">';
        $htmlField .= '<input type="hidden" id="'. $this->field->getFormName() . '-' . $key .'-_model" name="'. $this->field->getFormName() . '[' . $key . ']' .'[_model]" data-required="false" class="form-control" value="'. $this->pttForm->getEntityInfo()->getEntityName() .'">';
        $htmlField .= '</div>';

        return $htmlField;
    }

    protected function extraClassesForFieldContainer()
    {
        return 'form-group entity col-sm-12';
    }
}
