<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeUpload extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();

		//content here
		$html .= $this->label();

		// DIV 1: UPLOAD BUTTON
		$html .= '<div class="upload-file-container col-sm-12 nopadding">';
			$html .= '<a class="fakeClick">' . $this->pttTrans->trans('pick_file') . '</a><input type="file" class="chooseFile" ';
			$html .= $this->attributes() .'>';
		$html .= '</div>';
		// DIV 2: BARRA DE CARREGA
		$html .= '<div class="load-mode col-sm-12 nopadding">';
			$html .= '<div class="col-sm-9 nopadding">';
				$html .= '<div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 100%;height: 30px;padding:5px;border-radius: 4px;"><span>40%</span></div>';
			$html .= '</div>';	
			$html .= '<div class="col-sm-3 nopadding" style="text-align:right;"><a href="" class="btn btn-cancel" style="height: 30px;padding: 3px 15px;border-color: #fd4343;">'.$this->pttTrans->trans('cancel').'</a></div>';
		$html .= '</div>';
		// DIV 3: RESULT
		$html .= '<div class="result col-sm-12 nopadding">';
			$html .= '<span class="glyphicon glyphicon-ok">Missatge</span>';
			$html .= '<span class="glyphicon glyphicon-remove">Missatge</span>';
		$html .= '</div>';
		// DIV 4: FITXER, DOWNLOAD I DELETE
		$html .= '<div class="view-mode col-sm-12 nopadding" style="text-align:right;">';
			$html .= '<input class="col-sm-12 form-control" disabled type="text" value="'. $this->value .'" />';
			$html .= '<div class="action-buttons col-sm-12 nopadding">';
				$html .= '<a href="" class="btn btn-download">' . $this->pttTrans->trans('download') . '</a>';
				$html .= '<a href="" class="btn btn-delete">' . $this->pttTrans->trans('delete') . '</a>';
			$html .= '</div>';	
		$html .= '</div>';

		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group upload-field-container col-sm-' . $this->getWidth();
    }
}