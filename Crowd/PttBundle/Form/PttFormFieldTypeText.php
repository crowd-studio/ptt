<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeText extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<input type="text" ';
		if (isset($this->field->options['maxLength'])){
			$htmlField .= 'maxlength="'.$this->field->options['maxLength'].'" ';
		}
		$htmlField .= $this->attributes();
		$htmlField .= 'value=\'' . str_replace('\'', '&#039;', $this->value) . '\'';
		$htmlField .= '>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group text col-sm-12';
    }
}