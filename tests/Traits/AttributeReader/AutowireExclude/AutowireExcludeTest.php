<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\AutowireExclude;

use Kaspi\DiContainer\AttributeReader;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Traits\AttributeReader\AutowireExclude\Fixtures\ClassWillBeExcluded;
use Tests\Traits\AttributeReader\AutowireExclude\Fixtures\ClassWillNotBeExcluded;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\AttributeReader
 */
class AutowireExcludeTest extends TestCase
{
    public function testAutowireExclude(): void
    {
        $this->assertTrue(
            AttributeReader::isAutowireExclude(new ReflectionClass(ClassWillBeExcluded::class))
        );
    }

    public function testAutowireNotExclude(): void
    {
        $this->assertFalse(
            AttributeReader::isAutowireExclude(new ReflectionClass(ClassWillNotBeExcluded::class))
        );
    }
}
