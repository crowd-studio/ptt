<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Util;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class PttCache
{
    private $_cachePath = 'tmp/cache/';
    private $_cacheExtension = '.pttCache';
    private $_key = false;
    private $_absolutePath = '';
    private $cache;

    public function __construct($key = false)
    {
        $this->cache = new FilesystemAdapter();
        if ($key != false) {
            $this->_key = $key;
            // $this->_absolutePath = $this->_cachePath($key);
        }
    }

    public function store($data)
    {
        $cached = $this->cache->getItem($this->_key);

        $cached->set($data);
        $this->cache->save($cached);

        return $data;
    }

    public function retrieve()
    {
        $cached = $this->cache->getItem($this->_key);
        if (!$cached->isHit()) {
            return false;
        }

        return $cached->get();
    }

    public function remove($key = false)
    {
        $this->cache->deleteItem(($key) ? $key : $this->_key);
    }

    public function removeAll($key = '')
    {
        $this->cache->clear();
    }

    public function setKey($key)
    {
        $this->_key = $key;
    }
}