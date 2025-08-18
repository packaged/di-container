<?php

namespace Packaged\DiContainer;

class AttributeWatcher implements ReflectionObserver
{
  /**
   * @var \ReflectionAttribute[]
   */
  protected array $_attributes = [];

  /**
   * @return \ReflectionAttribute[]
   */
  public function attributes(): array
  {
    return $this->_attributes;
  }

  /**
   * @return $this
   *              Clear any stored attributes
   */
  public function clear(): static
  {
    $this->_attributes = [];
    return $this;
  }

  public function observe($reflection)
  {
    if($reflection instanceof \ReflectionClass || $reflection instanceof \ReflectionMethod)
    {
      $this->_attributes = array_merge($this->_attributes, $reflection->getAttributes());
    }
  }
}
