<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeNumber extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<input type="number" ';
		$htmlField .= $this->attributes();
		$htmlField .= 'value="' . $this->value . '"';
		$htmlField .= '>';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group number col-sm-' . $this->getWidth();
    }
}