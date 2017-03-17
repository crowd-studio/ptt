<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeUpload extends PttFormFieldType
{
    public function field()
    {
        $s3 = PttUtil::pttConfiguration('s3');

        $prefix = $s3['prodUrl'] . $this->entityInfo->getEntityName() . '-' . $this->field->name . '/';
        $name = ($this->value && $this->value != '') ? $prefix . $this->value : '';

        $download = ($name != '') ? $name : $prefix;

        $html = $this->start();

        //content here
        $html .= $this->label();

        $html .= '<input class="hidden" type="hidden" ' . $this->attributes() .' data-produrl="'. $s3['prodUrl'] .'" data-key="'. $s3['accessKey'] .'" data-folder="'. $this->entityInfo->getEntityName() . '-'. $this->field->name. '"  data-bucket="' . $s3['bucket'] . '" data-signer="' . $s3['signer'] . '" data-region="' . $s3['region'] . '" value="'. $this->value .'" />';

        // DIV 1: UPLOAD BUTTON
        $html .= '<div class="multipart-upload-container hidden">';
        $html .= '<a class="fakeClick">' . $this->pttTrans->trans('pick_file') . '</a><input type="file" class="chooseFile" ';
        $html .= $this->attributes() .'>';
        $html .= '</div>';
        // DIV 2: BARRA DE CARREGA
        $html .= '<div class="load-mode hidden">';
        $html .= '<div class="progress-bar-div col-xs-12 nopadding">';
        $html .= '<div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"><span>0%</span></div>';
        $html .= '</div>';
        $html .= '<div class="action-buttons col-sm-12"><a class="btn btn-cancel">'.$this->pttTrans->trans('cancel').'</a></div>';
        $html .= '</div>';
        // DIV 3: RESULT
        $html .= '<div class="result hidden">';
        $html .= '<div class="error-div col-xs-12 nopadding">';
        $html .= '<p class="upload-fail btn"></p>';
        $html .= '</div>';
        $html .= '<div class="action-buttons col-sm-12">';
        $html .= '<a class="btn btn-retry">' . $this->pttTrans->trans('retry') . '</a>';
        $html .= '</div>';
        $html .= '</div>';
        // DIV 4: FITXER, DOWNLOAD I DELETE
        $html .= '<div class="view-mode hidden">';
        $html .= '<input class="form-control" disabled type="text" value="'.$name.'" />';
        $html .= '<div class="action-buttons col-sm-12">';
        $html .= '<a class="btn btn-download" href="' . $download .'" download>' . $this->pttTrans->trans('download') . '</a>';
        $html .= '<a class="btn btn-delete">' . $this->pttTrans->trans('delete') . '</a>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= $this->end();

        return $html;
    }

    protected function extraClassesForFieldContainer()
    {
        return 'form-group multipart-upload col-sm-' . $this->getWidth();
    }
}
