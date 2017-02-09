<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Util;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PttTrans
{
    private $em;
    private $tokenStorage;
    private $container;
    private $request;
    private $languages;
    private $preferredLanguage;

    public function __construct(EntityManager $entityManager, TokenStorage $tokenStorage, ContainerInterface $serviceContainer)
    {
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->container = $serviceContainer;

        $metadata = $this->container->get('pttEntityMetadata');
        $languages = $metadata->getLanguages();
        $this->preferredLanguage = $metadata->getPreferredLanguage();

        $this->languages = [];

        foreach ($languages as $language) {
            try {
                $yaml = new Parser();
                $filePath = __DIR__ . '/../Resources/translations/' . $language->getCode() . '.yml';
                $transStrings = $yaml->parse(file_get_contents($filePath));

                $extendedFilePath = __DIR__ . "/../../../../../../app/config/ptt/translations/" . $language->getCode() . '.yml';
                if (file_exists($extendedFilePath) && is_file($extendedFilePath)) {
                    try {
                        $extendedTransStrings = $yaml->parse(file_get_contents($extendedFilePath));
                        $transStrings = array_merge($transStrings, $extendedTransStrings);
                    } catch (ParseException $e) {
                        throw new \Exception('Unable to parse the ' . $language->getCode() . '.yml file');
                    }
                }
                $this->languages[$language->getCode()] = $transStrings;

            } catch (ParseException $e) {
                throw new \Exception('Unable to parse the ' . $language->getCode() . '.yml file');
            }
        }

    }

    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function trans($key, $strings = false)
    {

        if($this->request){
            $language = (strpos($this->request->get('_route'),'-')) ? substr($this->request->get('_route'), -2) : $this->preferredLanguage->getCode();
        } else {
            $language = $this->preferredLanguage->getCode();
        }
        
        $transStrings = $this->languages[$language];

        if (is_string($key) && isset($transStrings[$key])) {
            $value = $transStrings[$key];
            if (is_string($strings)) {
                $value = str_replace('%@', (string)$strings, $value);
            } else if (is_array($strings)) {
                foreach ($strings as $string) {
                    $string = (string)$string;
                    $pos = strpos($value, '%@');
                    if ($pos !== false) {
                        $value = substr_replace($value, $string, $pos, strlen('%@'));
                    }
                }
            }
            return $value;
        } else {
            return $key;
        }

    }
}