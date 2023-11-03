<?php

namespace Packaged\Tests\DiContainer\Supporting;

class BasicObject
{
  public function missingService(UnusedInterface $require)
  {
    return $require;
  }
}