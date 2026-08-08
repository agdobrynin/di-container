<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\DefinitionsConfigurator;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DefinitionsConfigurator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\EventListener;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\TagsOnObjectDefinitionTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Bar;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Bat;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Baz;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Foo;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Fuz;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Maker;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\QuxInterface;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diValue;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(Tag::class)]
#[CoversClass(DefinitionsConfigurator::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(TagsOnObjectDefinitionTrait::class)]
#[CoversClass(TagsTrait::class)]
#[CoversClass(EventListener::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversFunction('Kaspi\DiContainer\diGet')]
#[CoversFunction('Kaspi\DiContainer\diCallable')]
#[CoversFunction('Kaspi\DiContainer\diValue')]
class DefinitionsConfiguratorFindTaggedDefinitionsTest extends DefinitionsConfiguratorAbstract
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configurator = new DefinitionsConfigurator(
            $this->definitionsLoaderMock,
            $this->removedDefinitionsIds,
            $this->parameters,
            $this->configuratorContexts,
            $this->definitionsConfiguratorEvent
        );
    }

    public function testFindTaggedDefinitionsWithAttribute(): void
    {
        $this->definitionsLoaderMock->method('isUseAttribute')
            ->willReturn(true)
        ;

        $this->definitionsLoaderMock->method('definitions')
            ->willReturnCallback(function () {
                yield Bar::class => diAutowire(Bar::class)
                    ->bindTag('tags.one')
                ;

                yield Baz::class => diAutowire(Baz::class);

                yield Foo::class => diAutowire(Foo::class);

                yield 'service.bar' => diGet(Bar::class);

                yield Bat::class => diAutowire(Bat::class);

                yield Fuz::class => diAutowire(Fuz::class)
                    ->bindTag(DiContainerInterface::class)
                    ->bindTag('tags.one')
                ;
            })
        ;

        /** @var array<class-string, DiDefinitionAutowire> $definitions */
        $definitions = [...$this->configurator->findTaggedDefinition(QuxInterface::class)];

        self::assertCount(2, $definitions);
        self::assertEquals(Bar::class, $definitions[Bar::class]->getDefinition()->getName());
        self::assertEquals(Baz::class, $definitions[Baz::class]->getDefinition()->getName());

        $secondDefinitions = [...$this->configurator->findTaggedDefinition('tags.one')];
        self::assertCount(4, $secondDefinitions);

        // configured via attribute
        self::assertEquals(Bar::class, $secondDefinitions[Bar::class]->getDefinition()->getName());
        // configured via attribute
        self::assertEquals(Bat::class, $secondDefinitions[Bat::class]->getDefinition()->getName());
        // configured via attribute
        self::assertEquals(Foo::class, $secondDefinitions[Foo::class]->getDefinition()->getName());
        // configured via bingTag
        self::assertEquals(Fuz::class, $secondDefinitions[Fuz::class]->getDefinition()->getName());
    }

    public function testFindTaggedDefinitionsForNonTaggedObject(): void
    {
        $this->definitionsLoaderMock->method('definitions')
            ->willReturnCallback(function () {
                yield 'email.admin' => diValue('admin@example.com')
                    ->bindTag('tags.emails')
                ;

                yield Baz::class => diAutowire(Baz::class);

                yield Foo::class => diAutowire(Foo::class);

                yield 'service.bar' => diGet(Bar::class);

                yield 'email.manager' => diCallable([Maker::class, 'managerEmail'])
                    ->bindTag('tags.emails')
                ;

                yield Bat::class => diAutowire(Bat::class);

                yield Fuz::class => diAutowire(Fuz::class)
                    ->bindTag(DiContainerInterface::class)
                    ->bindTag('tags.one')
                ;
            })
        ;

        /** @var array<class-string, DiDefinitionValue> $definitions */
        $definitions = [...$this->configurator->findTaggedDefinition('tags.emails')];

        self::assertCount(2, $definitions);

        self::assertEquals('admin@example.com', $definitions['email.admin']->getDefinition());
        self::assertEquals([Maker::class, 'managerEmail'], $definitions['email.manager']->getDefinition());
    }
}
