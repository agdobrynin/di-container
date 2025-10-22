<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\SetupAttribute;

use Generator;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Traits\AttributeReader\SetupAttribute\Fixtures\SetupImmutableOnConstructor;
use Tests\Traits\AttributeReader\SetupAttribute\Fixtures\SetupImmutableOnDestructor;
use Tests\Traits\AttributeReader\SetupAttribute\Fixtures\SetupOnConstructor;
use Tests\Traits\AttributeReader\SetupAttribute\Fixtures\SetupOnDestructor;

/**
 * @covers \Kaspi\DiContainer\Attributes\Setup
 * @covers \Kaspi\DiContainer\Attributes\SetupImmutable
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class SetupAttributeTest extends TestCase
{
    protected object $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait {
                getSetupAttribute as public;
            }
        };
    }

    public function dataProviderFailSetups(): Generator
    {
        yield 'on construct #Setup' => [SetupOnConstructor::class, '::__construct()'];

        yield 'on construct #SetupImmutable' => [SetupImmutableOnConstructor::class, '::__construct()'];

        yield 'on destructor #Setup' => [SetupOnDestructor::class, '::__destruct()'];

        yield 'on destructor #SetupImmutable' => [SetupImmutableOnDestructor::class, '::__destruct()'];
    }

    /**
     * @dataProvider dataProviderFailSetups
     */
    public function testReadSetupAttributeOnConstructor(string $class, string $method): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use attribute.+'.$method.'/');

        $this->reader->getSetupAttribute(new ReflectionClass($class))->valid();
    }
}
