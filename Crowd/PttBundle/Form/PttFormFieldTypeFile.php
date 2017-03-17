<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeFile extends PttFormFieldType
{
    public function field()
    {
        $html = $this->start();

        if ($this->field->options['type'] == 'image' || $this->field->options['type'] == 'gallery') {
            $sizes = [];
            $maxW = 0;
            $maxH = 0;
            $maximum = 0;
            foreach ($this->field->options['sizes'] as $size) {
                if ($size['h'] =="m") {
                    $maximum = $size['w'];
                } elseif ($size['w'] == 0 && $size['h'] == 0) {
                    $sizes[] = $this->pttTrans->trans('free_size');
                } else {
                    $sizes[] = $size['w'] . 'x' . $size['h'] ;
                    if ($maxW < $size['w']) {
                        $maxW = $size['w'];
                    }
                    if ($maxH < $size['h']) {
                        $maxH = $size['h'];
                    }
                }
            }
            // $append = ' (' . implode(', ', $sizes) . ')';
            if ($maxW == 0) {
                $maxW = '...';
            }
            if ($maxH == 0) {
                $maxH = '...';
            }
            $append = ' ('.$maxW.'x'.$maxH.')';
            if ($maximum>0) {
                $append .= ' and max ' . $maximum . 'px';
            }
        } else {
            $append = '';
        }

        $html .= $this->label(false, $append);

        $htmlField = '<div class="upload-file-container ';
        if ($this->value != '') {
            $htmlField .= 'hidden';
        }

        $htmlField .= '"><a class="fakeClick">' . $this->pttTrans->trans('pick_file') . '</a><input type="file" class="chooseFile" ';
        $htmlField .= $this->attributes() .'>';


        if ($this->field->options['type'] == 'gallery' && $this->value == null) {
            $htmlField .= '<input type="hidden" class="gallery-input" ' . $this->attributes() .'>';
        }

        $class = '';
        if ($this->field->options['type'] != 'file') {
            $class = 'img-input';
        }
        $htmlField .= '<div class="row '. $class .' image-container hidden col-sm-12">
		<img class="preview-image" src="#"/>';

        $boolRemove = false;
        if (isset($this->field->options['delete'])) {
            if ($this->field->options['delete'] != false) {
                $htmlField .= '<a class="btn btn-xs btn-danger remove-image">x</a>';
                $boolRemove = true;
            }
        } else {
            $htmlField .= '<a class="btn btn-xs btn-danger remove-image">x</a>';
            $boolRemove = true;
        }

        $htmlField .= '</div></div>';

        if ($this->value != '') {
            if ($this->field->options['type'] == 'gallery') {
                $type = '_image';
            } else {
                $type = '_' . $this->field->options['type'];
            }

            $htmlField .= $this->{$type}($boolRemove);
        }

        $html .= $htmlField;
        $html .= $this->end();

        return $html;
    }

    private function _image($boolRemove)
    {
        $fileNameArray = explode('.', $this->value);
        $extension = end($fileNameArray);

        if (PttUploadFile::_toCDN($this->field)) {
            $largeName = $this->_urlPrefix() . $this->value;
            $smallName = $this->_urlPrefix() . $this->value;
        } else {
            $size = ($extension != 'gif') ? $this->field->options['sizes'][0] : ['w' => 0, 'h' => 0];
            $size2 = ($extension != 'gif') ? end($this->field->options['sizes']) : ['w' => 0, 'h' => 0];

            $largeName = $this->_urlPrefix() . $size2['w'] . '-' . $size2['h'] . '-' . $this->value;
            $smallName = $this->_urlPrefix() . $size['w'] . '-' . $size['h'] . '-' . $this->value;
        }

        $name = $this->field->getFormName($this->languageCode);
        $name = substr($name, 0, strlen($name) - 1) . '-delete]';

        $delete = '';
        if ($boolRemove) {
            $delete = '<a class="btn btn-xs btn-danger remove-image">x</a>';
        }

        $html = '
      		<div class="preview image col-sm-12">
      			<a title="' . $this->pttTrans->trans('view_in_larger_size') . '" href="' . $largeName . '" target="_blank">
      				<img src="' . $smallName . '">
      			</a>
      			<input type="hidden" name="' . $name . '" value="0" data-id="'. $this->value .'">
      			'.$delete.'
      		</div>';
        return $html;
    }

    private function _svg($boolRemove)
    {
        $path = $this->_urlPrefix() . $this->value;

        $name = $this->field->getFormName($this->languageCode);
        $name = substr($name, 0, strlen($name) - 1) . '-delete]';

        $delete = '';
        if ($boolRemove) {
            $delete = '<a class="btn btn-xs btn-danger remove-image">x</a>';
        }

        $html = '
		<div class="preview image col-sm-12">
			<a title="' . $this->pttTrans->trans('view_in_larger_size') . '" href="' . $path . '" target="_blank">
				<img src="' . $path . '">
			</a>
			'.$delete.'
		</div>';
        return $html;
    }

    private function _file($boolRemove)
    {
        $extension = str_replace('.', '', PttUtil::extension($this->value));

        $name = $this->field->getFormName($this->languageCode);
        $name = substr($name, 0, strlen($name) - 1) . '-delete]';

        $delete = '';
        if ($boolRemove) {
            $delete = '<a class="btn btn-xs btn-danger remove-image">x</a>';
        }

        $html = '
		<div class="preview file">
			<div class="extension">
				' . $extension . '
			</div>
			<div class="action">
				<a title="' . $this->pttTrans->trans('download_file') . '" href="' . $this->_urlPrefix() . $this->value . '" target="_blank">' . $this->pttTrans->trans('download_file') . '</a>
			</div>
			<input type="hidden" name="' . $name . '" value="0">
			'.$delete.'
		</div>
		';
        return $html;
    }

    private function _urlPrefix()
    {
        if (isset($this->field->options['s3']) && $this->field->options['s3']) {
            return PttUtil::pttConfiguration('s3')['prodUrl'] . PttUtil::pttConfiguration('s3')['dir'] . '/';
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
