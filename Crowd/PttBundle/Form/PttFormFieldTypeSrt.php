<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeSrt extends PttFormFieldType
{
    public function field()
    {
        $html = $this->start();
        $html .= $this->label();

        $htmlField = '<div class="upload-file-container ';
        if (!count($this->value)) {
            $htmlField .= 'hidden';
        }

        $htmlField .= '"><a class="fakeClick">' . $this->pttTrans->trans('pick_file') . '</a><input type="file" class="chooseFile" ';
        $htmlField .= $this->attributes() .'>';

        $htmlField .= '<div class="row image-container hidden col-sm-12"><img class="preview-image" src="#"/>';

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
        $htmlField .= $this->_file($boolRemove);

        $html .= $htmlField;
        $html .= $this->end();

        return $html;
    }

    private function _file($boolRemove)
    {
        $name = $this->field->getFormName($this->languageCode);
        $name = $name . '-delete]';

        $delete = '';
        if ($boolRemove) {
            $delete = '<a class="btn btn-xs btn-danger remove-image">x</a>';
        }

        $html = '
    		<div class="preview file">
      			<div class="action">
      				<a title="' . $this->pttTrans->trans('download_file') . '" target="_blank">' . $this->pttTrans->trans('download_file') . '</a>
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
