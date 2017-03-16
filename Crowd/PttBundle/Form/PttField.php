<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttField
{
	//Required
	public $name;
	public $type;
	public $options;

	//Optional
	public $mapped;
	public $validations;

	//Others
	public $translatable;

	private $formName;

	public function __construct($field, $formName, $translatable = false){
		$this->formName = $formName;
		$this->translatable = $translatable;

		$this->_mapField($field);
	}

	private function _mapField($field){
		if (!isset($field['name'])) {
				throw new \Exception('The property name was not found');
		}

		if (!isset($field['type'])) {
				throw new \Exception('The property type was not found');
		}

		if (!isset($field['options'])) {
				throw new \Exception('The property options was not found');
		}

		$this->name = $field['name'];
		$this->type = $field['type'];
		$this->options = $field['options'];
		$this->validations = (isset($field['validations'])) ? $field['validations'] : false;
		$this->mapped = (isset($field['mapped'])) ? $field['mapped'] : true;
		$this->showErrors = (isset($field['showErrors'])) ? $field['showErrors'] : true;
	}

	public function getFormName($languageCode = false, $append = ''){
		return ($languageCode) ? $this->formName . '[' . $languageCode . '][' . $this->name . ']' . $append : $this->formName . '[' . $this->name . ']' . $append;
	}

	public function getSimpleFormName(){
		return $this->formName;
	}

	public function getFormId($languageCode = false, $append = ''){
		return str_replace('--', '-', str_replace('[', '-', str_replace(']', '', $this->getFormName($languageCode, $append))));
	}

	public function setFormName($formName){
		$this->formName = $formName;
	}

	public function getFormNameSec(){
		return $this->formName;
	}
}
