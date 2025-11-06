<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader;

use Attribute;
use Generator;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class AttributeOnParameterTest extends TestCase
{
    use AttributeReaderTrait;

    private DiContainerInterface $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(DiContainerInterface::class);
    }

    public function testWithoutAttribute(): void
    {
        $fn = static fn (
            #[FooAttr, Inject] // Tests\Traits\AttributeReader\Inject attribute from current namespace
            string $param
        ) => true;

        self::assertFalse(
            $this->getAttributeOnParameter(new ReflectionParameter($fn, 'param'), $this->container)->valid()
        );
    }

    /**
     * @dataProvider dataProviderIntersectAttribute
     */
    public function testIntersectAttribute(ReflectionParameter $parameter, string $messageMatches): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessageMatches($messageMatches);

        $this->getAttributeOnParameter($parameter, $this->container)->valid();
    }

    public function dataProviderIntersectAttribute(): Generator
    {
        // Inject::class, ProxyClosure::class, TaggedAs::class, InjectByCallable::class

        yield 'Inject and ProxyClosure' => [
            new ReflectionParameter(static fn (#[FooAttr, \Kaspi\DiContainer\Attributes\Inject, ProxyClosure('log')] $param) => true, 'param'),
            '/Only one of the attributes.+#\[.+Inject\], #\[.+ProxyClosure\].+\[ \<required\> \$param \]/',
        ];

        yield 'Inject, TaggedAs, ProxyClosure' => [
            new ReflectionParameter(static fn (#[InjectByCallable('services.foo'), \Kaspi\DiContainer\Attributes\Inject, TaggedAs('tags.bar')] $param) => true, 'param'),
            '/Only one of the attributes.+#\[.+InjectByCallable\], #\[.+Inject\].+#\[.+TaggedAs\].+\[ \<required\> \$param \]/',
        ];
    }
}

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class FooAttr {}

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class Inject {}
