<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Twig;

use \Twig_Extension;
use \Twig_Filter_Method;
use \Twig_SimpleFunction;
use \Twig_Function_Method;
use \Michelf\Markdown;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttTrans;

class PttTwigExtension extends Twig_Extension
{

    private $em;
    private $securityContext;
    private $request;
    private $kernel;
    private $pttTrans;

    public function __construct(\Doctrine\ORM\EntityManager $em, $securityContext, KernelInterface $kernel) {
        $this->em = $em;
        $this->securityContext = $securityContext;
        $this->kernel = $kernel;
    }

    public function setRequest(RequestStack $request_stack)
    {
        $this->request = $request_stack->getCurrentRequest();
    }

    public function setPttTrans($pttTrans)
    {
        $this->pttTrans = $pttTrans;
    }

    public function getName()
    {
        return 'pttTwigExtension';
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('dynamicValue', array($this, 'dynamicValue'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('order', array($this, 'order'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('md2html', array($this, 'md2html'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('trans', array($this, 'trans'), array(
                'is_safe' => array('html')
            )),
        );
    }

    public function getFunctions()
    {
        return array(
            'info' => new Twig_Function_Method($this, 'info'),
            'isDebug' => new Twig_Function_Method($this, 'isDebug', array(
                'is_safe' => array('html')
            )),
            'userIsRole' => new Twig_Function_Method($this, 'userIsRole'),
            'userRole' => new Twig_Function_Method($this, 'userRole'),
            'isAllowed' => new Twig_Function_Method($this, 'isAllowed'),
            'filter' => new Twig_Function_Method($this, 'filter', array(
                'is_safe' => array('html')
            )),
            'asset_exists' => new \Twig_Function_Method($this, 'asset_exists'),
        );
    }

    public function md2html($text)
    {
        return \Michelf\Markdown::defaultTransform($text);
    }

    public function dynamicValue($entity, $key)
    {
        $method = 'get' . ucfirst($key);
        if (method_exists($entity, $method)) {
            $value = $entity->{$method}();
            if (is_bool($value)) {
                return ($value) ? $this->pttTrans->trans('yes') : $this->pttTrans->trans('no');
            }
            if (strpos(strtolower($key), 'url') !== false) {
                if (trim($value) != '') {
                    return '<a target="_blank" href="http://' . $value . '">' . $value . '</a>';
                }
            }
            if (strpos(strtolower($key), 'email') !== false) {
                return '<a href="mailto:' . $value . '">' . $value . '</a>';
            }
            return $value;
        } else {
            if (isset($entity[$key])) {
                return $entity[$key];
            } else {
                return '';
            }
        }
    }

    public function filter($key, $entityName)
    {
        $cookies = $this->request->cookies;
        $name = 'filter-' . strtolower($entityName) . '-' . $key;
        if ($cookies->has($name)) {
            return $cookies->get($name);
        } else {
            return '';
        }
    }

    public function order($text, $key, $entityName)
    {
        $cookies = $this->request->cookies;
        $name = ucfirst($entityName) . '-' . $key;
        if ($cookies->has($name)) {
            $value = $cookies->get($name);
            if ($value == 'asc') {
                return $text . ' &darr;';
            } else if ($value == 'desc') {
                return $text . ' &uarr;';
            }
        } else {
            return $text;
        }
    }

    public function info($key, $key2 = null)
    {
        $fields = PttUtil::pttConfiguration();
        if (isset($fields[$key])) {
            if ($key2 != null && isset($fields[$key][$key2])) {
                return $fields[$key][$key2];
            }
            return $fields[$key];
        } else {
            return '';
        }
    }

    public function isDebug()
    {
        return DEBUG;
    }

    public function userIsRole($role)
    {
        $user = $this->securityContext->getToken()->getUser();
        return ($user->getRole() == $role);
    }

    public function userRole()
    {
        $user = $this->securityContext->getToken()->getUser();
        return $user->getRole();
    }

    public function isAllowed($info)
    {
        if (isset($info['roles'])) {
            $userRole = $this->userRole();
            if (isset($info['roles']['allowed'])) {
                return (in_array($userRole, $info['roles']['allowed']));
            }
            if (isset($info['roles']['forbidden'])) {
                return !(in_array($userRole, $info['roles']['forbidden']));
            }
        }
        return true;
    }

    public function asset_exists($path)
    {
        $webRoot = $this->kernel->getRootDir() . '/../web/';
        $toCheck = $webRoot . $path;

        // check if the file exists
        if (!is_file($toCheck))
        {
            return false;
        }

        // check if file is well contained in web/ directory (prevents ../ in paths)
        if (strncmp($webRoot, $toCheck, strlen($webRoot)) !== 0)
        {
            return false;
        }

        return true;
    }

    public function trans($key, $strings = false)
    {
        return $this->pttTrans->trans($key, $strings);
        // return $key;
    }
}