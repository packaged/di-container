<?php

namespace Packaged\Tests\DiContainer\Supporting;

use Packaged\DiContainer\ReflectionObserver;

class SecondObserver implements ReflectionObserver
{
  protected array $_attributes = [];

  public function attributes(): array
  {
    return $this->_attributes;
  }

  public function observe($reflection): void
  {
    if($reflection instanceof \ReflectionClass || $reflection instanceof \ReflectionMethod)
    {
      $attributes = $reflection->getAttributes();

      foreach($attributes as $attribute)
      {
        if ($attribute->getName() != 'Packaged\Tests\DiContainer\abc')
        {
          $this->_attributes[] = $attribute;
        }
      }
    }
  }
}
