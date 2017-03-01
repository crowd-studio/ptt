<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeFavicon extends PttFormFieldType
{
	$sizes = ['w' => '512', 'h' => '512'];

	public function field()
	{
		$html = $this->start();

		$append = ' ('.$this->sizes['w'].'x'.$this->sizes['h'].')';
		$html .= $this->label(false, $append);

		$htmlField = '<div class="upload-file-container ';
		if ($this->value != '') {
			$htmlField .= 'hidden';
		}

		$htmlField .= '"><a class="fakeClick">' . $this->pttTrans->trans('pick_file') . '</a><input type="file" class="chooseFile" ';
		$htmlField .= $this->attributes() .'>';

		$class = '';
		if($this->field->options['type'] != 'file'){
			$class = 'img-input';
		}
		$htmlField .= '<div class="row '. $class .' image-container hidden col-sm-12">
		<img class="preview-image" src="#"/>';

		$boolRemove = false;
		if (isset($this->field->options['delete'])){
			if($this->field->options['delete'] != false){
				$htmlField .= '<a class="btn btn-xs btn-danger remove-image">x</a>';	
				$boolRemove = true;
			}
		} else {
			$htmlField .= '<a class="btn btn-xs btn-danger remove-image">x</a>';
			$boolRemove = true;
		}

		$htmlField .= '</div></div>';

		$htmlField .= $this->_image($boolRemove);

		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	private function _image($boolRemove)
	{
		$fileNameArray = explode('.', $this->value);
		$extension = end($fileNameArray);

		$uploadToCDN = (isset($this->field->options['cdn']) && $this->field->options['cdn']) ? true : false;
		if($uploadToCDN){
			$largeName = $this->_urlPrefix() . $this->value;
		} else {
			$largeName = $this->_urlPrefix() . $this->sizes['w'] . '-' . $this->sizes['h'] . '-' . $this->value;
		}

		$name = $this->field->getFormName($this->languageCode);
		$name = substr($name, 0, strlen($name) - 1) . '-delete]';

		$delete = '';
		if($boolRemove){
			$delete = '<a class="btn btn-xs btn-danger remove-image">x</a>';
		}

		$html = '
		<div class="preview image col-sm-12">
			<a title="' . $this->pttTrans->trans('view_in_larger_size') . '" href="' . $largeName . '" target="_blank">
				<img src="' . $largeName . '">
			</a>
			<input type="hidden" name="' . $name . '" value="0" data-id="'. $this->value .'">
			'.$delete.'
		</div>';
		return $html;
	}

	private function _urlPrefix()
	{
		if (isset($this->field->options['s3']) && $this->field->options['s3']) {
			$s3 = PttUtil::pttConfiguration('s3');
			return $s3['prodUrl'] . $s3['dir'] . '/';
		} else {
			return (isset($this->field->options['cdn']) && $this->field->options['cdn']) ? PttUtil::pttConfiguration('cdn')['prodUrl'] : PttUtil::pttConfiguration('prefix') . PttUtil::pttConfiguration('images');
		}
	}

	protected function extraClassesForField()
	{
		return '';
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group file col-sm-' . $this->getWidth();
    }
}
