<?php

namespace Packaged\Tests\DiContainer\Supporting;

use Packaged\DiContainer\DependencyFactory;

class TestFactory implements DependencyFactory
{
  protected $_generated = 0;
  protected $_classMap = [
    ServiceInterface::class => ServiceOne::class,
  ];

  public function generate(string $abstract, array $parameters = [])
  {
    $this->_generated++;
    $class = $this->_classMap[$abstract] ?? $abstract;
    return new $class(...$parameters);
  }

  public function getGenerated(): int
  {
    return $this->_generated;
  }
}
