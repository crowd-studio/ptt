<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Routing;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Finder\Finder;
use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Controller\PttController;

class PttRouting
{
	public function routingCollection(array $info = array())
	{
		$collection = new RouteCollection();

		foreach ($info as $bundleInfo) {
			$collection = $this->_addRoutes($collection, $bundleInfo);
		}

		return $collection;
	}

	private function _addRoutes($collection, $info)
	{

		if (!array_key_exists('bundle', $info)) {
			throw new \Exception('The bundle name must be defined');
		} else {
			$bundleName = $info['bundle'];
		}
		if (!array_key_exists('controllerClassPrefix', $info)) {
			throw new \Exception('The controller class prefix must be defined');
		} else {
			$controllerClassPrefix = $info['controllerClassPrefix'];
			$controllersPath = BASE_DIR . 'src/' . str_replace('\\', '/', $controllerClassPrefix);
		}

		$finder = new Finder();
		$finder->files()->in($controllersPath);

		$configuration = PttUtil::pttConfiguration('admin');
		$adminUrl = (isset($configuration['admin_url'])) ? $configuration['admin_url'] : '/admin';

		foreach ($finder as $file) {

		    $controllerStringPos = strpos($file->getFileName(), 'Controller');

		    if ($controllerStringPos !== false) {

		    	$name = PttUtil::extractControllerName($file->getFileName());

		    	$controllerName = $controllerClassPrefix . $name . 'Controller';
		    	$controller = new $controllerName();

		    	$isSubclass = is_subclass_of($controller, 'Crowd\PttBundle\Controller\PttController');
		    	$shouldCreateDefaultMethods = ($isSubclass) ? $controller->shouldCreateDefaultMethods() : false;
		    	unset($controller);

		    	if ($isSubclass && $shouldCreateDefaultMethods) {

		    		$nameLower = strtolower($name);
			    	$routePrefix = $adminUrl . '/' . $nameLower;

			    	$collection->add($nameLower . '_list', new Route($routePrefix . '/list/{page}', array(
					    '_controller' => $bundleName . ':' . $name . ':list',
					    'page' => 1
					)));

					$collection->add($nameLower . '_create', new Route($routePrefix . '/create', array(
					    '_controller' => $bundleName . ':' . $name . ':edit',
					    'id' => null
					)));

			    	$collection->add($nameLower . '_edit', new Route($routePrefix . '/edit/{id}', array(
					    '_controller' => $bundleName . ':' . $name . ':edit',
					    'id' => null
					)));

					$collection->add($nameLower . '_delete', new Route($routePrefix . '/delete/{id}', array(
					    '_controller' => $bundleName . ':' . $name . ':delete',
					)));

					$collection->add($nameLower . '_order', new Route($routePrefix . '/order/', array(
					    '_controller' => $bundleName . ':' . $name . ':order',
					)));

					$collection->add($nameLower . '_last', new Route($routePrefix . '/last/', array(
					    '_controller' => $bundleName . ':' . $name . ':last',
					)));
		    	}
		    }
		}

		$collection->add('media_upload', new Route('/ptt/media/upload/', array(
					    '_controller' => 'PttBundle:PttMedia:upload',
					)));

		$collection->add('media_autocomplete', new Route('/ptt/media/autocomplete/', array(
					    '_controller' => 'PttBundle:PttMedia:autocomplete',
					)));

		return $collection;
	}
}