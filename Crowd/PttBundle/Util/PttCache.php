<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Util;

class PttCache
{
    private $_cachePath = 'tmp/cache/';
    private $_cacheExtension = '.pttCache';
    private $_key = false;

    public function __construct($key = false)
    {
        if ($key != false) {
            $this->_key = $key;
        }
    }

    public function store($data, $dataTime = false, $key = false)
    {
        $key = ($key != false) ? $key : $this->_key;

        if ($dataTime && $dataTime instanceof \DateTime) {
            $timestamp = $dataTime->getTimestamp();
        } else {
            $timestamp = 0;
        }

        $storeData = array(
            'time' => time(),
            'data-time' => $timestamp,
            'data' => serialize($data)
        );

        $path = $this->_cachePath($key);

        file_put_contents($path, json_encode($storeData));

        return $data;
    }

    public function retrieve($dataTime = false, $key = false)
    {
        $key = ($key != false) ? $key : $this->_key;

        $data = $this->_read($key);
        if ($data) {
            if ($dataTime && $dataTime instanceof \DateTime) {
                if ($data['data-time'] >= $dataTime->getTimestamp()) {
                    return $data['data'];
                } else {
                    return false;
                }
            } else {
                return $data['data'];
            }
        } else {
            return false;
        }
    }

    public function remove($key = false)
    {
        $key = ($key != false) ? $key : $this->_key;
        $path = $this->_fileExists($key);
        if ($path) {
            unlink($path);
        }
    }

    public function removeAll()
    {
        $files = glob(BASE_DIR . $this->_cachePath . '*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file)) {
                unlink($file); // delete file
            }
        }
    }

    public function setKey($key)
    {
        $this->_key = $key;
    }

    private function _read($key)
    {
        if ($this->_fileExists($key)) {
            $data = file_get_contents($this->_cachePath($key));
            $data = json_decode($data, true);
            $data['data'] = unserialize($data['data']);
            return $data;
        } else {
            return false;
        }
    }

    private function _fileExists($key)
    {
        $path = $this->_cachePath($key);
        if (file_exists($path) && is_file($path)) {
            return $path;
        } else {
            return false;
        }
    }

    private function _cachePath($key)
    {
        return BASE_DIR . $this->_cachePath . sha1($key) . $this->_cacheExtension;
    }
}