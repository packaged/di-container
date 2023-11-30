<?php

namespace Packaged\Tests\DiContainer\Supporting;

use Packaged\DiContainer\Resolvable;

class ResolvableObject implements Resolvable
{
  protected ?ServiceInterface $_svc = null;

  public function resolveWith(ServiceInterface $svc = null)
  {
    $this->_svc = $svc ?? $this->_svc;
    return $this;
  }

  public function getSvc(): ?ServiceInterface
  {
    return $this->_svc;
  }

}
