<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttFormRender;
use Crowd\PttBundle\Util\PttSave;
use Crowd\PttBundle\Util\PttFormValidations;
use Crowd\PttBundle\Util\PttUtil;

class PttHelperFormFieldTypeEntity
{
    private $entityInfo;
    private $pttForm;
    private $sentData;
    private $field;
    private $fields;
    private $formName;
    private $formId;
    private $rows;

    public function __construct($entityInfo, $field, $formName = '', $formId = '', $sentData = false)
    {
        $this->entityInfo = $entityInfo;
        $this->pttForm = $entityInfo->getForm();

        $this->field = $field;


        $this->sentData = $sentData;
        $this->formName = $formName;
        $this->formId = $formId;

        $this->fields = PttUtil::fields($this->pttForm->getContainer()->get('kernel'), $this->pttForm->getBundle(), $this->field['entity']);
    }

    public function formForEntity($entity, $key = false)
    {
        $pttFormRender = new PttFormRender($this->pttForm, $entity, $this->fields, $this->formName, $this->formId);
        return $pttFormRender->perform($key);
    }

    public function save()
    {
        $this->_updateFields();
        $pttFormSave = new PttSave($this->pttForm, $this->entity, $this->fields, $this->sentData);

        return $pttFormSave->perform();
    }

    public function validate()
    {
        $this->entity->beforeSave($this->sentData);

        $pttFormValidations = new PttFormValidations($this->pttForm, $this->entity, $this->fields, $this->sentData);
        $this->entity = $pttFormValidations->perform();
    }
}
