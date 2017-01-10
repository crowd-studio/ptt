<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\Security\Core\SecurityContext;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeInstagram extends PttFormFieldType
{
	public function field()
	{
		$html = $this->start();
		$html .= $this->label();

		$htmlField = '<input type="hidden" ' . $this->attributes() . ' value="' . $this->value . '">';

		$htmlField .= '<div class="login" ';
		if ($this->value != ''){
			$htmlField .= 'style="display:none" ';
		}

		$htmlField .= '>';

		if ($this->entityInfo->get('id')){
				$instaArray = PttUtil::pttConfiguration('insta');
				$url = 'https://api.instagram.com/oauth/authorize/?client_id='. $instaArray["apiKey"] .'&redirect_uri='.urlencode($instaArray['callback']. '?user=' . $this->entityInfo->get('id')).'&scope=relationships+likes+public_content+basic&response_type=code';
				$htmlField .= '<a class="btn btn-md btn-primary btn-sort btn-login" href="'.$url.'">' . $this->pttTrans->trans('login') . '</a>';
		} else {
			$htmlField = '<p>Primero crea el usuario.</p>';
		}

		$htmlField .= '</div>';

		if ($this->value != ''){
			$htmlField .= '<div class="logout">';

		    if (!function_exists('curl_init')){
		        die('Sorry cURL is not installed!');
		    }
		    $ch = curl_init('https://api.instagram.com/v1/users/self/?access_token='.$this->value);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			$response = curl_exec($ch);
			curl_close($ch);

			$insta = json_decode($response);
			$htmlField .= '<div class="img"><img src="' . $insta->data->profile_picture . '"></div>';
			$htmlField .= '<p>' . $insta->data->username . '</p>';
			$htmlField .= '<a class="btn btn-md btn-primary btn-sort btn-instagram">Desvincular</a>';

			$htmlField .= '</div>';
		}
		
		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group text col-sm-' . $this->getWidth();
    }
}