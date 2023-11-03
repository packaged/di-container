<?php
namespace Packaged\DiContainer;

use Packaged\Helpers\Objects;
use function class_exists;
use function is_scalar;

class DependencyInjector
{
  const MODE_IMMUTABLE = 'i';
  const MODE_MUTABLE = 'm';

  //Generators
  protected array $_factories = [];

  //Shared Instances
  protected array $_instances = [];

  public function factory($abstract, callable $generator, $mode = self::MODE_MUTABLE)
  {
    $this->_factories[$abstract] = ['generator' => $generator, 'mode' => $mode];
    return $this;
  }

  public function removeFactory($abstract)
  {
    unset($this->_factories[$abstract]);
    return $this;
  }

  public function removeShared($abstract)
  {
    if(isset($this->_instances[$abstract]) && $this->_instances[$abstract]['mode'] !== self::MODE_IMMUTABLE)
    {
      unset($this->_instances[$abstract]);
    }
    return $this;
  }

  /**
   * @param       $abstract
   * @param array $parameters
   * @param bool  $shared
   *
   * @return mixed
   * @throws \Exception
   */
  public function retrieve($abstract, array $parameters = [], bool $shared = true)
  {
    if($shared && isset($this->_instances[$abstract]))
    {
      return $this->_buildInstance($this->_instances[$abstract]['instance'], $parameters);
    }
    if(isset($this->_factories[$abstract]))
    {
      $instance = $this->_factories[$abstract]['generator'](...$parameters);
      if($instance !== null)
      {
        if($shared)
        {
          $this->share($abstract, $instance, $this->_factories[$abstract]['mode']);
        }
        return $instance;
      }
    }
    throw new \Exception("Unable to retrieve " . basename($abstract));
  }

  /**
   * @param       $instance
   * @param array $parameters
   *
   * @return mixed
   */
  protected function _buildInstance($instance, array $parameters = [])
  {
    if(is_scalar($instance) && class_exists($instance))
    {
      return Objects::create($instance, $parameters);
    }
    return $instance;
  }

  public function share($abstract, $instance, $mode = self::MODE_MUTABLE)
  {
    if($instance !== null)
    {
      if(!isset($this->_instances[$abstract]) || $this->_instances[$abstract]['mode'] !== self::MODE_IMMUTABLE)
      {
        $this->_instances[$abstract] = ['instance' => $instance, 'mode' => $mode];
      }
    }
    return $this;
  }

  /**
   * Check to see if an abstract has a shared instance already bound
   *
   * @param             $abstract
   * @param string|null $checkMode optionally check the correct mode is also set
   *
   * @return bool
   */
  public function hasShared($abstract, string $checkMode = null): bool
  {
    $exists = isset($this->_instances[$abstract]);
    return !$exists || $checkMode === null ? $exists : $this->_instances[$abstract]['mode'] === $checkMode;
  }

  public function isAvailable($abstract, $shared = null): bool
  {
    if($shared === false)
    {
      return isset($this->_factories[$abstract]);
    }
    return isset($this->_factories[$abstract]) || isset($this->_instances[$abstract]);
  }

  protected function _resolveParameters(\ReflectionMethod $reflection, array $parameters = []): array
  {
    $dependencies = [];
    foreach($reflection->getParameters() as $parameter)
    {
      $pType = $parameter->getType();
      if($pType && !$pType->isBuiltin())
      {
        try
        {
          $dependencies[] = $this->retrieve($pType->getName());
        }
        catch(\Exception $e)
        {
          if($pType->allowsNull())
          {
            $dependencies[] = null;
            continue;
          }
          throw $e;
        }
      }
    }
    return array_merge($dependencies, $parameters);
  }

  public function resolveMethod(object $object, ?string $method, ...$parameters): mixed
  {
    $reflection = new \ReflectionMethod($object, $method);
    return $reflection->invokeArgs($object, $this->_resolveParameters($reflection, $parameters));
  }

  public function resolveObject(string $className, ...$parameters): object
  {
    $reflection = new \ReflectionClass($className);
    $constructor = $reflection->getConstructor();
    if($constructor)
    {
      return $reflection->newInstanceArgs($this->_resolveParameters($constructor, $parameters));
    }
    return $reflection->newInstance();
  }
}
