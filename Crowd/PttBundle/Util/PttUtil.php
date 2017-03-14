<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Util;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Finder\Finder;

class PttUtil
{

    public static function pttConfiguration($sub = false, $required = true)
    {
        $yaml = new Parser();

        $filePath = __DIR__ . "/../../../../../../app/config/ptt.yml";

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new \Exception('The file app/config/ptt.yml was not found');
        }

        $fields = $yaml->parse(file_get_contents($filePath));

        if (!isset($fields['bundles'])) {
            throw new \Exception('The key bundles was not defined in the ptt.yml file');
        }

        if ($sub) {
            if (isset($fields[$sub])) {
                return $fields[$sub];
            } else {
                if ($required) {
                    throw new \Exception('The key ' . $sub . ' was not found in the ptt.yml file');
                } else {
                    return false;
                }
            }
        }

        return $fields;
    }

    public static function splitAtUpperCase($s) {
        return preg_split('/(?=[A-Z])/', $s, -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function bundle($s, $split = '/')
    {
        $arr = explode($split, $s);
        foreach ($arr as $item) {
            if (strlen(trim($item)) > 0) {
                if (strpos($item, 'Bundle') !== false) {
                    return $item;
                }
            }
        }
    }

    public static function token($length = 8, $strength = 4, $stamp = true)
    {
        $vowels = 'aeuy';
        $consonants = 'bdghjmnpqrstvz';
        if ($strength & 1) {
            $consonants .= 'BDGHJLMNPQRSTVWXZ';
        }
        if ($strength & 2) {
            $vowels .= "AEUY";
        }
        if ($strength & 4) {
            $consonants .= '23456789';
        }
        if ($strength & 8) {
            $consonants .= '@#$%';
        }

        $password = '';
        $alt = time() % 2;
        for ($i = 0; $i < $length; $i++) {
            if ($alt == 1) {
                $password .= $consonants[(rand() % strlen($consonants))];
                $alt = 0;
            } else {
                $password .= $vowels[(rand() % strlen($vowels))];
                $alt = 1;
            }
        }

        if($stamp == true) $password .= time();

        return $password;
    }

    static public function slugify($text)
    {
        if (strpos($text, '&')) {
            $text = str_replace('&', 'and', $text);
        }
        $text = PttUtil::textify($text);
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        if (empty($text)){return 'n-a';}
        return $text;
    }

    static public function textify($string)
    {
        return strtolower(trim(preg_replace('~[^0-9a-z]+~i','-',preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'))),' '));
    }

    static public function extension($filename)
    {
        $ext = strtolower(strrchr($filename, '.'));
        return $ext;
    }

    static public function checkTypeForFile($filename, $type = 'image')
    {
        $ext = PttUtil::extension($filename);

        if ($type == 'image') {
            if (!in_array(strtolower($ext), array('.png', '.jpg', '.jpeg', '.gif'))) {
                return false;
            } else {
                return $filename;
            }
        } else if ($type == 'pdf') {
            if (!in_array(strtolower($ext), array('.pdf'))) {
                return false;
            } else {
                return $filename;
            }
        } else {
            return $filename;
        }
    }

    static public function extractControllerName($filename)
    {
        $controllerStringPos = strpos($filename, 'Controller');
        return substr($filename, 0, $controllerStringPos);
    }

    static public function getSVGContent($uploadsPath, $fileName){
        $finder = new Finder();
        $finder->files()->in(trim($uploadsPath, '/'))->name($fileName);
        $contents ='';
        foreach ($finder as $file) {
            if ($file->getExtension() == 'svg'){
                $contents = $file->getContents();
            }
        }
        return $contents;
    }

    static public function sendMail($to, $subject, $render){
        $params = PttUtil::pttConfiguration('mailer');

        $transporter = \Swift_SmtpTransport::newInstance($params['host'], $params['port'])->setUsername($params['user'])->setPassword($params['password']);
        $mailer = \Swift_Mailer::newInstance($transporter);

        $message = \Swift_Message::newInstance()
          ->setSubject($subject)
          ->setFrom($params['user'])
          ->setTo($to)
          ->setCC('pau@crowd-studio.com')
          ->setBody($render, 'text/html');
        $mailer->send($message);

        return true;
    }

    static public function image($img, $size){
        if($img != ''){
            $end = explode('.', $img);
            $size = (strtolower(array_pop($end)) != 'gif') ? $size[0] . '-' . $size[1] . '-' : '0-0-';
            $img = PttUtil::uploadUrl() . $size . $img;
        }

        return $img;
    }

    static public function uploadUrl(){
      $s3 = PttUtil::pttConfiguration('s3');
      return (isset($s3['force']) && $s3['force']) ? $s3['prodUrl'] . $s3['dir'] . '/' : '/uploads/';
    }
}
