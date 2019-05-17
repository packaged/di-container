<?php
namespace Packaged\Tests\DiContainer\Supporting;

class TestObject
{
  public $params = [];

  public function __construct(array $params = null)
  {
    $this->params = $params;
  }

  public function paramCount()
  {
    return count($this->params);
  }
}
