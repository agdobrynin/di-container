<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\ClassOne;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiFactory::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionFactory::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
#[CoversClass(Helper::class)]
class DiFactoryTest extends TestCase
{
    public function testDiFactory(): void
    {
        $container = (new DiContainerFactory())->make();

        $myClass = $container->get(ClassOne::class);

        $this->assertEquals('Piter', $myClass->name);
        $this->assertEquals(22, $myClass->age);
        $this->assertSame($myClass, $container->get(ClassOne::class));
    }
}
