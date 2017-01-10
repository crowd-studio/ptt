<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeTextarea extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();
		$html .= $this->label();

		$extraAttr = '';
		$extraHtml = '';
		if (isset($this->field->options['type'])) {
			$type = $this->field->options['type'];
			switch ($type) {
				case 'markdown':
					$extraAttr = 'data-type="markdown" data-hidden-buttons="cmdCode"';
					$extraHtml = '<p class="help-block">Learn the <a href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet" target="_blank">Markdown basics</a>.</p>';
					break;
			}
		}

		$htmlField = '<textarea ' . $extraAttr . ' ';
		$htmlField .= $this->attributes();
		$htmlField .= '>' . $this->value . '</textarea>';
		$htmlField .= $extraHtml;

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group textarea col-sm-' . $this->getWidth();
    }
}