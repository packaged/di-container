<?php

namespace Packaged\Tests\DiContainer\Supporting;

class BasicObject
{
  protected $_value;

  public function missingService(UnusedInterface $require)
  {
    return $require;
  }

  public function setValue($value)
  {
    $this->_value = $value;
    return $this;
  }

  public function getValue()
  {
    return $this->_value;
  }
}