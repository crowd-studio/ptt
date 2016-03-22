<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;
use Abraham\TwitterOAuth\TwitterOAuth;

use Crowd\PttBundle\Util\PttUtil;

class PttFormFieldTypeTwitter extends PttFormFieldType
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

		$twitterArray = PttUtil::pttConfiguration('twitter');	
		if ($this->entityInfo->get('id')){
			
			$connection = new TwitterOAuth($twitterArray["apiKey"], $twitterArray["secretKey"]);
	        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $twitterArray['callback']));
	        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

	        $session = $this->container->get('session');
	        $session->set('oauth_token', $request_token['oauth_token']);
	        $session->set('oauth_token_secret', $request_token['oauth_token_secret']);
	        $session->set('user_edit_id', $this->entityInfo->get('id'));
			$htmlField .= '<a class="btn btn-md btn-primary btn-sort btn-login" href="'.$url.'">' . $this->pttTrans->trans('login') . '</a>';
		} else {
			$htmlField = '<p>Primero crea el usuario.</p>';
		}

		$htmlField .= '</div>';

		if ($this->value != ''){
			$htmlField .= '<div class="logout">';
			$user = $this->entityInfo->getEntity();

			$connection = new TwitterOAuth($twitterArray["apiKey"], $twitterArray["secretKey"], $user->getTwitterOAuth(), $user->getTwitterOAuthSecret());
			$userData = $connection->get("account/verify_credentials");

			$htmlField .= '<div class="img"><img src="' . $userData->profile_image_url . '"></div>';
			$htmlField .= '<p>' . $userData->screen_name . '</p>';
			$htmlField .= '<a class="btn btn-md btn-primary btn-sort btn-twitter">Desvincular</a>';

			$htmlField .= '</div>';
		}
		
		$html .= $htmlField;
		$html .= $this->end();

		return $html;
	}

	protected function extraClassesForFieldContainer()
    {
        return 'form-group text col-sm-12';
    }
}