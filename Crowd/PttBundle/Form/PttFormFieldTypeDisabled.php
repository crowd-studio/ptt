<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;
use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeDisabled extends PttFormFieldType
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
			if($this->value->format('Y') > -1){
				$this->value = $this->value->format('d/m/Y');
			} else {
				$this->value = null;
			}
		}
		$repository = $this->container->get('pttEntityMetadata')->respositoryName($this->field->options['entity']);
		$entity = $this->em->getRepository($repository)->find($this->value);

		$value = (isset($entity[$this->field->options['column']])) ? $entity[$this->field->options['column']] : '';

		$htmlField = '<input type="text" data-language="' . $language . '" ';
		$htmlField .= $this->attributes();
		$htmlField .= 'value="' . $value . '"';
		$htmlField .= ' disabled >';

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group text col-sm-12';
    }
}