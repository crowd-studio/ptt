<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttFaviconGenerator;
use WideImage;

class PttUploadFile
{
    public static function uploadCanvas($fileData, $sizes, $uploadToS3 = false)
    {
        $fileData = base64_decode(str_replace('data:image/png;base64,', '', $fileData));
        $uploadsUrl = PttUtil::pttConfiguration('images');
        $uploadName = 'webcam-' . PttUtil::token(100) . '.jpg';
        $tmpSaveThumbPath = WEB_DIR . $uploadsUrl . $uploadName;

        file_put_contents($tmpSaveThumbPath, $fileData);

        if (count($sizes)) {
            foreach ($sizes as $size) {
                $height = $size['h'];
                $width = $size['w'];
                $filename = $width . '-' . $height . '-' . $uploadName;
                $saveThumbPath = WEB_DIR . $uploadsUrl . $filename;

                $realSize = getimagesize($file);
                if ($height == 'm') {
                    $height = $width;
                    if ($realSize[0] > $realSize[1]) { // Més ample
                        $height = round(($size['w'] * $realSize[1]) / $realSize[0]);
                    } else { // Més alta o igual
                        $width = round(($size['w'] * $realSize[0]) / $realSize[1]);
                    }
                    \WideImage\WideImage::load($tmpSaveThumbPath)->resize($width, $height, 'outside')->saveToFile($saveThumbPath, 100);
                    \WideImage\WideImage::load($saveThumbPath)->crop('center', 'center', $width, $height)->saveToFile($saveThumbPath);
                } elseif ($width != 0 && $height != 0) {
                    \WideImage\WideImage::load($tmpSaveThumbPath)->resize($width, $height, 'outside')->saveToFile($saveThumbPath, 100);
                    \WideImage\WideImage::load($saveThumbPath)->crop('center', 'center', $width, $height)->saveToFile($saveThumbPath);
                }

                if ($uploadToS3) {
                    PttUploadFile::_uploadToS3($saveThumbPath, $filename);
                }
            }
        }

        if ($uploadToS3) {
            PttUploadFile::_uploadToS3($tmpSaveThumbPath, $uploadName);
        }

        return $uploadName;
    }

    public static function generateFavicon($fileName, $favicon, $url)
    {
        $s3 = PttUtil::pttConfiguration('s3');
        $uploadUrl = (isset($s3['force']) && $s3['force']) ? $s3['prodUrl'] . $s3['dir'] . '/' : $url . '/uploads/';

        $prefix = '512-512-';
        $file = $uploadUrl . $prefix . $fileName;

        $generator = new PttFaviconGenerator($favicon['key']);
        $options = $favicon['options'];
        $options['general']['src'] = $file;
        $response = $generator->generateFavicon($options);
        PttUploadFile::deleteFavicons();

        $response->downloadAndUnpack('_/', 'favicon');
        unlink(__DIR__ . '/../../../../../../web/_/favicon.zip'); //delete zip file
    }

    public static function deleteFavicons()
    {
        $files = glob(__DIR__ . '/../../../../../../web/_/favicon/*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file); // delete file
            }
        }
    }

    public static function upload($file, $field = false)
    {
        $type = (isset($field->options['type'])) ? $field->options['type'] : 'image';
        $validFilename = PttUtil::checkTypeForFile($file->getClientOriginalName(), $type);
        if (!$validFilename) {
            return '';
        }

        switch ($type) {
            case 'image':
                return PttUploadFile::_uploadImage($file, $field);
                break;
            case 'gallery':
                return PttUploadFile::_uploadImage($file, $field);
                break;
            case 'file':
                return PttUploadFile::_uploadFile($file, $field);
                break;
            case 'svg':
                return PttUploadFile::_uploadFile($file, $field);
                break;
            default:
                return '';
                break;
        }
    }

    private static function _extensionAndCompression($extension)
    {
        switch (strtolower($extension)) {
            case 'gif':
                return array('gif', 0);
                break;
            case 'png':
                return array('png', 0);
                break;
            default:
                return array('jpg', 100);
                break;
        }
    }

    private static function _uploadImage($file, $field)
    {
        $originalName = $file->getClientOriginalName();
        $originalNameArray = explode('.', $originalName);
        $extension = end($originalNameArray);
        list($extension, $level) = PttUploadFile::_extensionAndCompression($extension);
        $file = $file->getPathName();
        $token = PttUtil::token(100);
        $uploadName = $token . '.' . $extension;
        $uploadsUrl = PttUtil::pttConfiguration('images');

        if ($extension != 'gif') {
            $sizes = ($file && isset($field->options['sizes'])) ? $field->options['sizes'] : [['h' => 0, 'w' => 0]];

            $realSize = getimagesize($file);

            if (count($sizes)) {
                foreach ($sizes as $size) {
                    $height = $size['h'];
                    $width = $size['w'];

                    if ($height == 'm') {
                        $height = $width;
                        if ($realSize[0] > $width || $realSize[1] > $height) {
                            if ($realSize[0] > $realSize[1]) {
                                // Més ample
                                $height = round(($size['w'] * $realSize[1]) / $realSize[0]);
                            } else {
                                // Més alta o igual
                                $width = round(($size['w'] * $realSize[0]) / $realSize[1]);
                            }
                        }
                    } elseif ($size['w'] == 0) {
                        $width = round(($size['h'] * $realSize[0]) / $realSize[1]);
                    } elseif ($size['h'] == 0) {
                        $height = round(($size['w'] * $realSize[1]) / $realSize[0]);
                    }

                    $filename = $size['w'] . '-' . $size['h'] . '-' . $uploadName;
                    $saveThumbPath = WEB_DIR . $uploadsUrl . $filename;

                    if ($width == 0 && $height == 0) {
                        \WideImage\WideImage::load($file)->saveToFile($saveThumbPath);
                    } else {
                        \WideImage\WideImage::load($file)->resize($width, $height, 'outside')->saveToFile($saveThumbPath, $level);
                        \WideImage\WideImage::load($saveThumbPath)->crop('center', 'center', $width, $height)->saveToFile($saveThumbPath);
                    }
                    if (PttUploadFile::toS3($field)) {
                        PttUploadFile::_uploadToS3($saveThumbPath, $filename);
                    }
                }
            }
        } else {
            $filename = '0-0-' . $uploadName;
            $saveThumbPath = WEB_DIR . $uploadsUrl . $filename;
            if (move_uploaded_file($file, $saveThumbPath)) {
                if ($uploadToS3) {
                    PttUploadFile::_uploadToS3($saveThumbPath, $filename);
                }
            }
        }
        return $uploadName;
    }

    private static function _uploadFile($file, $field)
    {
        $filename = $file->getPathName();
        $token = PttUtil::token(100);
        $uploadsUrl = PttUtil::pttConfiguration('images');
        $uploadName = $token . PttUtil::extension($file->getClientOriginalName());

        $file->move(WEB_DIR . $uploadsUrl, $uploadName);

        if (PttUploadFile::toS3($field)) {
            PttUploadFile::_uploadToS3(WEB_DIR . $uploadsUrl . $uploadName, $uploadName);
        }

        return $uploadName;
    }

    private static function _uploadToS3($filepath, $filename)
    {
        $s3ClassPath = __DIR__ . '/../../../../../../vendor/tpyo/amazon-s3-php-class/S3.php';
        if (!file_exists($s3ClassPath) || !is_file($s3ClassPath)) {
            throw new \Exception('The class S3.php was not found at path ' . $s3ClassPath);
        }

        $s3 = PttUtil::pttConfiguration('s3');

        \S3::setAuth($s3['accessKey'], $s3['secretKey']);
        \S3::putObject(\S3::inputFile($filepath, false), $s3['bucket'], $s3['dir'] . '/' . $filename, \S3::ACL_PUBLIC_READ);
        unlink($filepath);
    }

    public static function deleteFile($field, $name)
    {
        if (PttUploadFile::toS3($field)) {
            PttUploadFile::_deleteS3($name);
        } else {
            PttUploadFile::_delete($name);
        }
    }

    private static function _delete($name)
    {
        try {
            $uploadsUrl = PttUtil::pttConfiguration('images');
            foreach (glob(WEB_DIR . "*-". $name) as $filename) {
                unlink($filename);
            }
        } catch (Exception $e) {
        }
    }

    private static function _deleteS3($name)
    {
        // $s3 = PttUtil::pttConfiguration('s3');

        // \S3::setAuth($s3['accessKey'], $s3['secretKey']);
        // \S3::deleteObject($s3['bucket'], $s3['dir'] . '/' . $filename);
    }

    private static function _toS3($field)
    {
        return ((isset($field->options['s3']) && $field->options['s3']) || (isset($field->options['cdn']) && $field->options['cdn']));
    }
}
