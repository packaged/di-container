<?php

namespace Packaged\DiContainer;

interface ReflectionInterrupt
{
  // Should Interrupt Method Execution
  public function shouldInterruptMethod(): bool;

  // Interrupt Method Execution
  public function interruptMethod(): mixed;

  // Should Interrupt Class Creation
  public function shouldInterruptClass(): bool;

  // Interrupt Class Creation
  public function interruptClass(): object;
}
