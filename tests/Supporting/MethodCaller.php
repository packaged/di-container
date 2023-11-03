<?php

namespace Packaged\Tests\DiContainer\Supporting;

class MethodCaller
{
  protected ServiceInterface $service;

  public function __construct(ServiceInterface $service)
  {
    $this->service = $service;
  }

  public function darkMode(string $text = 'moon')
  {
    return ($this->service->process() ? 'dark' : 'light') . ' ' . $text;
  }
}