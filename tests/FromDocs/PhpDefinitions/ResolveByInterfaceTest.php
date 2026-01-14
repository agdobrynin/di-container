<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassFirst;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassInterface;

use function array_merge;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
class ResolveByInterfaceTest extends TestCase
{
    public function testResolveByClass(): void
    {
        $definition = [
            ClassInterface::class => diAutowire(ClassFirst::class)
                // bind by name
                ->bindArguments(file: '/var/log/app.log'),
        ];

        $container = (new DiContainerFactory())->make($definition);
        $myClass = $container->get(ClassInterface::class);

        $this->assertEquals('/var/log/app.log', $myClass->file);
    }

    public function testResolveByByReference(): void
    {
        $classesDefinitions = [
            diAutowire(ClassFirst::class)
                // bind by index
                ->bindArguments('/var/log/app.log'),
        ];

        // ... many definitions ...

        $interfacesDefinitions = [
            ClassInterface::class => diGet(ClassFirst::class),
        ];

        $container = (new DiContainerFactory())->make(
            array_merge($classesDefinitions, $interfacesDefinitions)
        );

        $myClass = $container->get(ClassInterface::class);

        $this->assertEquals('/var/log/app.log', $myClass->file);
    }
}
