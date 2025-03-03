<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\AutowireExclude;

use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\AttributeReader\AutowireExclude\Fixtures\ClassWillBeExcluded;
use Tests\Traits\AttributeReader\AutowireExclude\Fixtures\ClassWillNotBeExcluded;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 */
class AutowireExcludeTest extends TestCase
{
    use AttributeReaderTrait;
    use DiContainerTrait;

    public function testAutowireExclude(): void
    {
        $this->assertTrue(
            $this->isAutowireExclude(new \ReflectionClass(ClassWillBeExcluded::class))
        );
    }

    public function testAutowireNotExclude(): void
    {
        $this->assertFalse(
            $this->isAutowireExclude(new \ReflectionClass(ClassWillNotBeExcluded::class))
        );
    }
}
