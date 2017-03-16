<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormValidationEntity extends PttFormValidation {

    protected $pttForm;
    protected $container;

    public function __construct(PttForm $pttForm, PttField $field, $languageCode = false){
        parent::__construct($pttForm, $field, $languageCode);

        $this->pttForm = $pttForm;
        $this->container = $pttForm->getContainer();
    }

	public function isValid()
	{
        $pttHelper = new PttHelperFormFieldTypeEntity($this->entityInfo, $this->field, $this->container, $this->entityInfo->getEntityManager());

        $sentData = $this->_sentValue();
        $errors = array();

		    if (is_array($sentData) && count($sentData)) {
            foreach ($sentData as $key => $entityData) {
                if ($key != -1) {
                    $entity = $pttHelper->entityForDataArray($entityData);
                    $form = $pttHelper->formForEntity($entity, $key);
                    $isValid = $form->isValid();
                    if (!$isValid) {
                        $errors[$key] = $form->getErrors();
                    }
                }
            }
        }

        if (count($errors)) {
            $this->pttForm->addError($this->field->name, $errors);
        }

        return true;
	}
}
