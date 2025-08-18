<?php

namespace Packaged\Tests\DiContainer;

use Packaged\DiContainer\AttributeWatcher;
use PHPUnit\Framework\TestCase;

#[abc]
#[def]
#[xyz]
class AttributeWatcherTest extends TestCase
{
  #[One]
  #[Two]
  public static function methodX()
  {
  }

  public function testAttributeWatcher()
  {
    $watcher = new AttributeWatcher();
    $this->assertEmpty($watcher->attributes());

    $reflection = new \ReflectionClass(static::class);
    $watcher->observe($reflection);

    static::assertNotEmpty($watcher->attributes());
    static::assertCount(3, $watcher->attributes());

    $watcher->clear();
    $this->assertEmpty($watcher->attributes());

    $reflection = new \ReflectionMethod(static::class, 'methodX');
    $watcher->observe($reflection);
    static::assertNotEmpty($watcher->attributes());
    static::assertCount(2, $watcher->attributes());
  }
}
