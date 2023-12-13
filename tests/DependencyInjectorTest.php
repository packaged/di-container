<?php

namespace Packaged\Tests\DiContainer;

use Packaged\DiContainer\DependencyInjector;
use Packaged\Tests\DiContainer\Supporting\BasicObject;
use Packaged\Tests\DiContainer\Supporting\Cache;
use Packaged\Tests\DiContainer\Supporting\CacheInterface;
use Packaged\Tests\DiContainer\Supporting\ExtendedBasicObject;
use Packaged\Tests\DiContainer\Supporting\MethodCaller;
use Packaged\Tests\DiContainer\Supporting\NeedyObject;
use Packaged\Tests\DiContainer\Supporting\ResolvableObject;
use Packaged\Tests\DiContainer\Supporting\ServiceInterface;
use Packaged\Tests\DiContainer\Supporting\ServiceOne;
use Packaged\Tests\DiContainer\Supporting\ServiceTwo;
use Packaged\Tests\DiContainer\Supporting\TestFactory;
use Packaged\Tests\DiContainer\Supporting\TestObject;
use Packaged\Tests\DiContainer\Supporting\UnusedInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class DependencyInjectorTest extends TestCase
{
  /**
   * @throws \Exception
   */
  public function testShare()
  {
    $di = new DependencyInjector();
    $this->assertFalse($di->hasShared('S'));
    $this->assertFalse($di->isAvailable('S'));
    $this->assertFalse($di->isAvailable('S', true));
    $di->share('S', null);
    $this->assertFalse($di->hasShared('S'));
    $class = new stdClass();
    $di->share('S', $class);
    $this->assertTrue($di->hasShared('S'));
    $this->assertTrue($di->isAvailable('S'));
    $this->assertFalse($di->isAvailable('S', false));
    $this->assertSame($class, $di->retrieve('S'));
    $di->removeShared('S');
    $this->assertFalse($di->hasShared('S'));

    $di->share("TEST", TestObject::class);
    $this->assertInstanceOf(TestObject::class, $di->retrieve("TEST"));
    $di->share("TEST", TestObject::class);
    /** @var TestObject $result */
    $result = $di->retrieve("TEST", [[1, 2, 3]]);
    $this->assertEquals(3, $result->paramCount());
  }

  /**
   * @throws \Exception
   */
  public function testShareImmutable()
  {
    $di = new DependencyInjector();
    $this->assertFalse($di->hasShared('S'));
    $this->assertFalse($di->hasShared('S', DependencyInjector::MODE_IMMUTABLE));
    $this->assertFalse($di->isAvailable('S'));
    $this->assertFalse($di->isAvailable('S', true));
    $di->share('S', null);
    $this->assertFalse($di->hasShared('S'));
    $class = new stdClass();
    $class->x = 'y';
    $di->share('S', $class, DependencyInjector::MODE_IMMUTABLE);
    $this->assertTrue($di->hasShared('S'));
    $this->assertTrue($di->hasShared('S', DependencyInjector::MODE_IMMUTABLE));
    $this->assertFalse($di->hasShared('S', DependencyInjector::MODE_MUTABLE));
    $this->assertTrue($di->isAvailable('S'));
    $this->assertFalse($di->isAvailable('S', false));
    $this->assertSame($class, $di->retrieve('S'));
    $di->removeShared('S');
    $this->assertSame($class, $di->retrieve('S'));
    $this->assertTrue($di->hasShared('S'));
    $this->assertTrue($di->hasShared('S', DependencyInjector::MODE_IMMUTABLE));
    $class2 = new stdClass();
    $class2->x = 'z';
    $di->share('S', $class2, DependencyInjector::MODE_IMMUTABLE);
    $this->assertSame($class, $di->retrieve('S'));
  }

  /**
   * @throws \Exception
   */
  public function testFactory()
  {
    $di = new DependencyInjector();
    $this->assertFalse($di->isAvailable('F'));
    $this->assertFalse($di->isAvailable('F', false));
    $this->assertFalse($di->isAvailable('F', true));
    $di->factory(
      'F',
      function (...$params) {
        $instance = new TestObject($params);
        return $instance;
      }
    );
    $this->assertTrue($di->isAvailable('F'));
    $this->assertTrue($di->isAvailable('F', false));
    $this->assertTrue($di->isAvailable('F', true));
    $this->assertFalse($di->hasShared('F'));

    /** @var TestObject $i */
    $i = $di->retrieve('F', ['one', 'two'], false);
    $this->assertInstanceOf(TestObject::class, $i);
    $this->assertEquals(2, $i->paramCount());
    $this->assertFalse($di->hasShared('F'));
    /** @var TestObject $i2 */
    $i2 = $di->retrieve('F', ['a', 'b', 'c'], true);
    $this->assertEquals(3, $i2->paramCount());
    $this->assertTrue($di->hasShared('F'));
    $this->assertTrue($di->hasShared('F', DependencyInjector::MODE_MUTABLE));

    $i3 = $di->retrieve('F');
    $this->assertSame($i2, $i3);
  }

  /**
   * @throws \Exception
   */
  public function testFactoryRemove()
  {
    $di = new DependencyInjector();
    $di->factory(
      'RF',
      function (...$params) {
        $instance = new TestObject($params);
        return $instance;
      }
    );

    $i4 = $di->retrieve('RF', ['one', 'two'], false);
    $this->assertInstanceOf(TestObject::class, $i4);

    $di->removeFactory('RF');
    $this->expectExceptionMessage("Unable to retrieve");
    $di->retrieve('RF', ['one', 'two'], false);
  }

  /**
   * @throws \Exception
   */
  public function testImmutableFactory()
  {
    $di = new DependencyInjector();
    $this->assertFalse($di->isAvailable('F'));
    $this->assertFalse($di->isAvailable('F', false));
    $this->assertFalse($di->isAvailable('F', true));
    $di->factory(
      'F',
      function (...$params) {
        $instance = new TestObject($params);
        return $instance;
      },
      DependencyInjector::MODE_IMMUTABLE
    );
    $this->assertTrue($di->isAvailable('F'));
    $this->assertTrue($di->isAvailable('F', false));
    $this->assertTrue($di->isAvailable('F', true));
    $this->assertFalse($di->hasShared('F'));

    /** @var TestObject $i */
    $i = $di->retrieve('F', ['one', 'two']);
    $this->assertInstanceOf(TestObject::class, $i);
    $this->assertEquals(2, $i->paramCount());
    $this->assertTrue($di->hasShared('F', DependencyInjector::MODE_IMMUTABLE));
    $this->assertFalse($di->hasShared('F', DependencyInjector::MODE_MUTABLE));
    $this->assertTrue($di->hasShared('F'));
    /** @var TestObject $i2 */
    $i2 = $di->retrieve('F', ['a', 'b', 'c']);
    $this->assertEquals(2, $i2->paramCount());
    $this->assertTrue($di->hasShared('F'));
    $this->assertSame($i, $i2);

    $i3 = $di->retrieve('F', ['a', 'z', 'x', 'y'], false);
    $this->assertEquals(4, $i3->paramCount());
    $this->assertNotSame($i2, $i3);
  }

  public function testResolveMethod()
  {
    $di = new DependencyInjector();
    $di->share(ServiceInterface::class, new ServiceOne());

    $tst = new TestObject();
    $result = $di->resolveMethod($tst, 'process', 'textValue');
    static::assertEquals("textValue without cache failed", $result);

    $di->share(ServiceInterface::class, new ServiceTwo());
    $result = $di->resolveMethod($tst, 'process', 'textValue');
    static::assertEquals("textValue without cache passed", $result);

    $di->share(ServiceInterface::class, new ServiceTwo());
    $di->share(CacheInterface::class, new Cache());
    $result = $di->resolveMethod($tst, 'process', 'textValue');
    static::assertEquals("textValue with cache passed", $result);

    $basic = new BasicObject();
    static::expectException(\Exception::class);
    static::expectExceptionMessage("Unable to retrieve " . basename(UnusedInterface::class));
    $di->resolveMethod($basic, 'missingService');
  }

  public function testResolveObject()
  {
    $di = new DependencyInjector();

    $di->share(ServiceInterface::class, new ServiceOne());
    $object = $di->resolveObject(NeedyObject::class);
    static::assertInstanceOf(NeedyObject::class, $object);
    static::assertFalse($object->process());

    $di->share(ServiceInterface::class, new ServiceTwo());
    $object = $di->resolveObject(NeedyObject::class);
    static::assertInstanceOf(NeedyObject::class, $object);
    static::assertTrue($object->process());

    $object = $di->resolveObject(BasicObject::class);
    static::assertInstanceOf(BasicObject::class, $object);
  }

  public function testRetrieveAll()
  {
    $di = new DependencyInjector();
    $di->share(ServiceInterface::class, new ServiceOne());
    $di->share(CacheInterface::class, new Cache());

    $all = $di->retrieveAll([ServiceInterface::class, CacheInterface::class, [TestObject::class, ['a', 'b']]]);

    static::assertCount(3, $all);
    static::assertInstanceOf(ServiceOne::class, $all[0]);
    static::assertInstanceOf(Cache::class, $all[1]);

    $tObj = $all[2];
    static::assertInstanceOf(TestObject::class, $tObj);
    static::assertEquals(2, $tObj->paramCount());
  }

  public function testResolve()
  {
    $di = new DependencyInjector();
    $resolved = $di->resolve(TestObject::class, ['a', 'b']);
    static::assertInstanceOf(TestObject::class, $resolved);
    static::assertEquals(2, $resolved->paramCount());

    $di->share(ServiceInterface::class, new ServiceOne());
    $result = $di->resolve(MethodCaller::class . ':darkMode', 'apple');
    static::assertEquals('light apple', $result);

    $di->share(ServiceInterface::class, new ServiceTwo());
    $result = $di->resolve(MethodCaller::class . ':darkMode', 'apple');
    static::assertEquals('dark apple', $result);
  }

  public function testAliasAbstract()
  {
    $di = new DependencyInjector();
    $src = new ExtendedBasicObject();
    $src->setValue('test-value');
    $di->share(BasicObject::class, $src);
    $di->aliasAbstract(ExtendedBasicObject::class, BasicObject::class);

    $resolved = $di->retrieve(ExtendedBasicObject::class);
    static::assertInstanceOf(ExtendedBasicObject::class, $resolved);
    static::assertEquals('test-value', $resolved->getValue());
  }

  public function testAliasAbstractIncorrectBinding()
  {
    $di = new DependencyInjector();
    $di->share(ServiceInterface::class, new ServiceOne());
    $di->aliasAbstract(ExtendedBasicObject::class, ServiceInterface::class);

    static::expectException(\Exception::class);
    static::expectExceptionMessage("Incorrect binding to " . basename(ExtendedBasicObject::class));
    $di->retrieve(ExtendedBasicObject::class);
  }

  public function testOnAfterResolve()
  {
    $di = new DependencyInjector();
    $di->share(BasicObject::class, (new BasicObject())->setValue('abc-def'));
    $basic1 = $di->retrieve(BasicObject::class);
    static::assertInstanceOf(BasicObject::class, $basic1);
    static::assertEquals('abc-def', $basic1->getValue());

    $di = new DependencyInjector();
    $di->onAfterResolve(function ($instance) {
      if($instance instanceof BasicObject)
      {
        $instance->setValue('after-resolve');
      }
    });

    $basic2 = $di->retrieve(BasicObject::class);
    static::assertInstanceOf(BasicObject::class, $basic2);
    static::assertEquals('after-resolve', $basic2->getValue());
  }

  public function testResolvable()
  {
    $di = new DependencyInjector();
    $di->share(ServiceInterface::class, new ServiceOne());

    $inst = $di->resolve(ResolvableObject::class);
    static::assertInstanceOf(ResolvableObject::class, $inst);
    static::assertInstanceOf(ServiceOne::class, $inst->getSvc());

    $inst2 = $di->resolved(new ResolvableObject());
    static::assertInstanceOf(ResolvableObject::class, $inst2);
    static::assertInstanceOf(ServiceOne::class, $inst2->getSvc());
  }

  public function testDependencyFactory()
  {
    $di = new DependencyInjector();
    $di->factory(ServiceInterface::class, TestFactory::class);
    $factory = $di->retrieve(TestFactory::class);
    static::assertInstanceOf(TestFactory::class, $factory);
    static::assertEquals(0, $factory->getGenerated());
    $svc1 = $factory->generate(ServiceOne::class);
    static::assertInstanceOf(ServiceOne::class, $svc1);
    static::assertEquals(1, $factory->getGenerated());

    $svc2 = $di->retrieve(ServiceInterface::class);
    static::assertInstanceOf(ServiceOne::class, $svc2);
    static::assertEquals(2, $factory->getGenerated());
  }
}
