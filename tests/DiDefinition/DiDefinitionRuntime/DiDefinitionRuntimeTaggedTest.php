<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionRuntime;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\AutowireExclude;
use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiRuntime::class)]
#[CoversClass(Tag::class)]
#[CoversClass(TagsTrait::class)]
class DiDefinitionRuntimeTaggedTest extends TestCase
{
    #[TestWith(['foo', null])]
    #[TestWith(['service.foo', NonExist::class])]
    #[TestWith([FooFail::class, null])]
    public function testCannotReadTagAttributes(string $id, ?string $classDefinition): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot get tags');

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(
                useAttribute: true,
            ))
        ;

        $d = new DiDefinitionRuntime($id, classDefinition: $classDefinition);
        $d->setContainer($mockContainer);

        $d->getTags();
    }

    #[TestWith([
        FooSuccess::class,
        null,
        [
            'tags.service_foo' => [
                'priority' => 100,
            ],
        ],
    ])]
    #[TestWith([
        'foo.service_one',
        FooSuccess::class,
        [],
    ])]
    #[TestWith([
        'foo.service_two',
        FooSuccess::class,
        [
            'tags.service_foo_two' => [],
        ],
    ])]
    #[TestWith([
        'foo.service_three',
        FooSuccess::class,
        [
            'tags.service_three.one' => [],
            'tags.service_three.two' => [
                'priority' => 100,
            ],
        ],
    ])]
    #[TestWith([
        BarSuccess::class,
        BarSuccess::class,
        [],
    ])]
    public function testReadTagAttributes(string $id, ?string $classDefinition, array $expectTags): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(
                useAttribute: true,
            ))
        ;
        $d = new DiDefinitionRuntime($id, classDefinition: $classDefinition);
        $d->setContainer($mockContainer);

        self::assertEquals($expectTags, $d->getTags());
    }
}

#[DiRuntime('foo.service')]
#[DiRuntime('foo.service')]
final class FooFail {}

#[DiRuntime]
#[DiRuntime(
    'foo.service_one',
    tags: [],
)]
#[DiRuntime(
    'foo.service_two',
    tags: new Tag('tags.service_foo_two'),
)]
#[DiRuntime(
    'foo.service_three',
    tags: [
        new Tag('tags.service_three.one'),
        new AutowireExclude(),
        new Tag('tags.service_three.two', priority: 100),
    ],
)]
#[Tag('tags.service_foo', priority: 100)]
final class FooSuccess {}

#[DiRuntime('bar.service', tags: new Tag('tags.bar_success'))]
#[DiRuntime(BarSuccess::class, tags: [])]
#[Tag('tags.bar_success')]
final class BarSuccess {}
