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

  // Alias Abstracts [abstract => [to, strict]]
  protected array $_aliases = [];

  // Post Resolver Callbacks
  protected array $_postResolver = [];

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
   * @param string $fromAbstract
   * @param string $toAbstract
   * @param bool   $strictResolution after resolution, perform a type check of the original
   *
   * @return $this
   */
  public function aliasAbstract(string $fromAbstract, string $toAbstract, bool $strictResolution = true)
  {
    if($fromAbstract != $toAbstract)
    {
      $this->_aliases[$fromAbstract] = [$toAbstract, $strictResolution];
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
        $instance = $this->_postResolve($instance);
        if($shared)
        {
          $this->share($abstract, $instance, $this->_factories[$abstract]['mode']);
        }
        return $instance;
      }
    }
    else if(isset($this->_aliases[$abstract]))
    {
      [$aliasTo, $strict] = $this->_aliases[$abstract];
      $resolved = $this->retrieve($aliasTo, $parameters, $shared);
      if($resolved && $strict && !($resolved instanceof $abstract))
      {
        throw new \Exception("Incorrect binding to " . basename($abstract));
      }
      return $resolved;
    }
    else if(class_exists($abstract))
    {
      return $this->resolve($abstract, ...$parameters);
    }

    throw new \Exception("Unable to retrieve " . basename($abstract));
  }

  public function retrieveAll(array $abstracts, bool $shared = true): array
  {
    $instances = [];
    foreach($abstracts as $abstract)
    {
      if(is_array($abstract))
      {
        $instances[] = $this->retrieve(array_shift($abstract), $abstract, $shared);
      }
      else
      {
        $instances[] = $this->retrieve($abstract, [], $shared);
      }
    }
    return $instances;
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
    $inputCount = count($parameters);
    if($reflection->getNumberOfParameters() <= $inputCount)
    {
      return $parameters;
    }

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
    return $this->_postResolve($reflection->invokeArgs($object, $this->_resolveParameters($reflection, $parameters)));
  }

  public function resolveObject(string $className, ...$parameters): object
  {
    $reflection = new \ReflectionClass($className);
    $constructor = $reflection->getConstructor();
    if($constructor)
    {
      return $this->_postResolve($reflection->newInstanceArgs($this->_resolveParameters($constructor, $parameters)));
    }
    return $this->_postResolve($reflection->newInstance());
  }

  public function resolve(string $class, ...$parameters): mixed
  {
    if(stristr($class, ':'))
    {
      [$className, $method] = explode(':', $class, 2);
      return $this->resolveMethod($this->resolveObject($className), $method, ...$parameters);
    }

    return $this->resolveObject($class, ...$parameters);
  }

  protected function _postResolve($instance)
  {
    foreach($this->_postResolver as $callback)
    {
      $callback($instance);
    }
    return $instance;
  }

  public function onAfterResolve(callable $callback)
  {
    $this->_postResolver[] = $callback;
    return $this;
  }
}
