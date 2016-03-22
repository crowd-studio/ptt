<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypePassword extends PttFormFieldType
{
	public function field()
	{

		$required = ($this->value == null) ? '*' : '';

		$html = $this->start();
		$html .= $this->label($required);

		$htmlField = '<input type="password" ';
		$htmlField .= $this->attributes($this->field->getFormId($this->languageCode, '-first'), $this->field->getFormName($this->languageCode, '[first]'));
		$htmlField .= 'value=""';
		$htmlField .= '>';

		$htmlField .= '<input type="password" ';
		$htmlField .= $this->attributes($this->field->getFormId($this->languageCode, '-repeat'), $this->field->getFormName($this->languageCode, '[repeat]'));
		$htmlField .= 'value=""';
		$htmlField .= '>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group password col-sm-12';
    }
}