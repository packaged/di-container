<?php

namespace Packaged\Tests\DiContainer\Supporting;

class NeedyObject
{
  public function __construct(protected ServiceInterface $service) { }

  public function process(): bool
  {
    return $this->service->process();
  }
}