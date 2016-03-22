<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypePanel extends PttFormFieldType
{

	public function field()
	{

		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<input type="hidden" value="' . $this->value . '"';
		$htmlField .= $this->attributes(false);
		$htmlField .= '/><div class="btn-group">';
		$htmlField .= $this->_static();
		$htmlField .= '</div>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	private function _static()
	{
		$html = '';
		if (isset($this->field->options['options'])) {
			$i = 0;
			foreach ($this->field->options['options'] as $key => $value) {
				$selected = ($this->_selected($i)) ? ' active' : '';
				$html .= '<button type="button" class="btn btn-sm btn-default' . $selected . '">' . $value . '</button>';
				$i++;
			}
		}
		return $html;
	}

	private function _selected($i)
	{
		$valueArr = str_split($this->value);
		return (isset($valueArr[$i]) && $valueArr[$i]) ? true : false;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group panel col-sm-12';
    }
}