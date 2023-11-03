<?php

namespace Packaged\Tests\DiContainer\Supporting;

class Cache implements CacheInterface
{
  public function get($key)
  {
    return $key . '-cached';
  }

}