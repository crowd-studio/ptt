<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeAutocomplete extends PttFormFieldType
{
	public function field()
	{

		$html = $this->start();
		$html .= $this->label();

		$html .= '<input type="text" value="' . $this->value . '"' $this->attributes(false) . '>';
		$html .= $this->end();

		return $html;
	}

	protected function extraAttrsForField()
	{
		return array('field' => htmlspecialchars(json_encode($this->field)));
	}

	protected function extraClassesForField()
	{
		return 'form-control autocomplete-input';
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group legend col-sm-' . $this->getWidth();
    }
}