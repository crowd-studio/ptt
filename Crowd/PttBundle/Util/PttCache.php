<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Util;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class PttCache
{
    private $_cachePath = 'tmp/cache/';
    private $_cacheExtension = '.pttCache';
    private $_key = false;
    private $_absolutePath = '';

    public function __construct($key = false)
    {

        if ($key != false) {
            $this->_key = $key;
            $this->_absolutePath = $this->_cachePath($key);
        }
    }

    public function store($data)
    {
        //Define your file path based on the cache one
        $filename = $this->_absolutePath;
        //Create your own folder in the cache directory
        $fs = new Filesystem();
        try {
            $fs->dumpFile($filename, $this->_encrypt($data));
        } catch (IOException $e) {
            echo "An error occured while creating your directory";
        }

        return $data;
    }

    public function retrieve()
    {
        $fs = new Filesystem();
        $filename = $this->_cachePath($this->_key);
        
        return ($fs->exists($filename)) ? $this->_decrypt(file_get_contents($filename)) : false;
        
    }

    public function remove($key = false)
    {
        $fs->remove(array('files', __DIR__ . "/../../../../../../" . $this->_cachePath, $this->_key . $this->_cacheExtension));
    }

    public function removeAll($key = '')
    {
        $files = glob(__DIR__ . "/../../../../../../" . $this->_cachePath . $key . '*.*'); // get all file names
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
            $data = file_get_contents($this->_absolutePath);
            $data = $this->_decrypt($data);
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
        return __DIR__ . "/../../../../../../" . $this->_cachePath . $key . $this->_cacheExtension;
    }

    private  function _encrypt ($input) {
        $output = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($this->_key), json_encode($input), MCRYPT_MODE_CBC, md5(md5($this->_key))));
        return $output;
    }
 
    private  function _decrypt ($input) {
        $output = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->_key), base64_decode($input), MCRYPT_MODE_CBC, md5(md5($this->_key))), "\0");
        return json_decode($output, true);
    }
}