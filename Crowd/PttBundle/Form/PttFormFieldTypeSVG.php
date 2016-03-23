<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;


class PttFormFieldTypeSVG extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<div class="upload-file-container ';
		if ($this->value != '') {
			$htmlField .= 'hidden';
		}

		$htmlField .= '"><a class="fakeClick">' . $this->pttTrans->trans('pick_file') . '</a><input type="file" class="chooseFile" ';
		$htmlField .= $this->attributes();
		$htmlField .= '><div class="row image-container svg hidden col-sm-12"><img class="preview-image" src="#"/><a class="btn btn-xs btn-danger remove-image">&#x2716;</a></div></div>';


		if ($this->value != '') {
			$html .= $this->_image();
		}

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	private function _image()
	{

		$path = $this->_urlPrefix() . $this->value;

		$name = $this->field->getFormName($this->languageCode);
		$name = substr($name, 0, strlen($name) - 1) . '-delete]';

		$html = '
		<div class="preview image col-sm-12">
			<a title="' . $this->pttTrans->trans('view_in_larger_size') . '" href="' . $path . '" target="_blank">
				<img src="' . $path . '">
			</a>
			<input type="hidden" name="' . $name . '" value="0">
			<a class="btn btn-xs btn-danger remove-image">&#x2716;</a>
		</div>';
		return $html;
	}

	private function _urlPrefix()
	{
		$s3 = PttUtil::pttConfiguration('s3');
		$uploadToS3 = (isset($this->field->options['s3']) && $this->field->options['s3']) ? true : false;
		if ($uploadToS3) {
			return $s3['prodUrl'] . $s3['dir'] . '/';
		} else {
			return '/uploads/';
		}
	}

	protected function extraClassesForField()
	{
		return '';
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group file col-sm-6';
    }
}