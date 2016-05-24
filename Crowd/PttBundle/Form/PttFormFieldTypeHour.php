<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;
use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeHour extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();
		$html .= $this->label();

		$language = PttUtil::pttConfiguration('preferredLanguage');
		if ($language == false) {
			$language = 'en';
		}

		if ($this->value instanceof \DateTime) {
			$this->value = $this->value->format('hh:MM');
		}

		$htmlField = '<input type="text" data-language="' . $language . '" ';
		$htmlField .= $this->attributes();
		$htmlField .= 'value="' . $this->value . '"';
		$htmlField .= '>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForField()
	{
		return 'form-control hourpicker';
	}

	protected function extraClassesForFieldContainer()
	{
		return 'form-group hourpicker col-sm-6';
	}
}