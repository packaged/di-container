<?php

namespace Packaged\Tests\DiContainer\Supporting;

class ServiceOne implements ServiceInterface
{
  public function process(): bool
  {
    return false;
  }
}