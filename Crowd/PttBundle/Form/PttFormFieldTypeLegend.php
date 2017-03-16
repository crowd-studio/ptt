<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeLegend extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();

		$legend = (isset($this->field->options['label'])) ? $this->field->options['label'] : '';
		$html .= '<legend>' . $legend . '</legend>';

		$html .= $this->end();
		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group legend col-sm-' . $this->getWidth();
    }
}
