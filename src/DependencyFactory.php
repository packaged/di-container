<?php

namespace Packaged\DiContainer;

interface DependencyFactory
{
  public function generate(string $abstract, array $parameters = []);
}
