<?php

namespace Packaged\Tests\DiContainer\Supporting;

class ServiceTwo implements ServiceInterface
{
  public function process(): bool
  {
    return true;
  }
}