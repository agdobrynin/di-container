<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByAutowireAttribute;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\Five;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\Four;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\One;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\Three;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\ThreeResetter;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\Two;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionGet::class)]
class ResolveByAutowireTest extends TestCase
{
    public function testAutowireAttributeWithSingletonTrueButContainerSetDefaultSingletonFalse(): void
    {
        $container = new DiContainer(config: new DiContainerConfig(
            isSingletonServiceDefault: false,
        ));

        $one = $container->get(One::class);

        $this->assertSame($one, $container->get(One::class));
    }

    public function testAutowireAttributeWithSingletonFalseButContainerSetDefaultSingletonTrue(): void
    {
        $container = new DiContainer(config: new DiContainerConfig(
            isSingletonServiceDefault: true,
        ));

        $two = $container->get(Two::class);

        $this->assertNotSame($two, $container->get(Two::class));
    }

    public function testAutowireWithResetter(): void
    {
        $container = new DiContainer(config: new DiContainerConfig());

        $definition = $container->getDefinition(Three::class);

        self::assertIsCallable($definition->getResetter());
        self::assertEquals([ThreeResetter::class, 'flush'], $definition->getResetter());
    }

    #[RequiresPhp('>= 8.4')]
    public function testInitializeLazyInjectionPhp84(): void
    {
        $container = new DiContainer(config: new DiContainerConfig());
        $five = $container->get(Five::class);

        $proxy = (new ReflectionClass(Four::class))->getProperty('foo');

        self::assertTrue($proxy->isLazy($five->getFour()));

        self::assertEquals('Lorem ipsum', $five->getFour()->getFoo());

        self::assertFalse($proxy->isLazy($five->getFour()));

        /** @var DiDefinitionAutowireInterface $definition */
        $definition = $container->getDefinition(Four::class);
        self::assertTrue($definition->isLazy());
    }

    #[RequiresPhp('< 8.4')]
    public function testInitializeLazyInjectionLessPhp84(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Cannot resolve parameter');

        (new DiContainer(config: new DiContainerConfig()))
            ->get(Five::class)
        ;
    }
}
