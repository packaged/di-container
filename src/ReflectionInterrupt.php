<?php

namespace Packaged\DiContainer;

interface ReflectionInterrupt
{
  // Should Interrupt Method Execution
  public function shouldInterruptMethod(): bool;

  // Interrupt Method Execution
  public function interruptMethod(): mixed;
}
