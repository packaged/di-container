<?php
namespace Packaged\Tests\DiContainer\Supporting;

class TestObject
{
  public array $params = [];

  public function __construct(array $params = null)
  {
    $this->params = $params ?? [];
  }

  public function paramCount()
  {
    return count($this->params);
  }

  public function process(ServiceInterface $service, ?CacheInterface $cache, string $input): string
  {
    return $input . ' ' . ($cache === null ? 'without' : 'with') . ' cache' . ($service->process() ? ' passed' :
        ' failed');
  }
}
