<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\DefinitionsConfigurator;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\DefinitionsConfigurator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\EventListener;
use Kaspi\DiContainer\Exception\NotFoundDefinition;
use Kaspi\DiContainer\Interfaces\Exceptions\NotFoundDefinitionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Bar;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Bat;
use Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures\Baz;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversClass(DefinitionsConfigurator::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(EventListener::class)]
#[CoversClass(NotFoundDefinition::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
class DefinitionsConfiguratorGetDefinitionTest extends DefinitionsConfiguratorAbstract
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

    public function testGetDefinitionSuccess(): void
    {
        $this->definitionsLoaderMock
            ->method('definitions')
            ->willReturnCallback(static function (): Generator {
                yield Bar::class => diAutowire(Bar::class);

                yield Bat::class => diAutowire(Bat::class);

                yield Baz::class => diAutowire(Baz::class);
            })
        ;

        self::assertEquals(
            Bat::class,
            $this->configurator->getDefinition(Bat::class)->getDefinition()->getName()
        );

        // get again from cache
        self::assertEquals(
            Bat::class,
            $this->configurator->getDefinition(Bat::class)->getDefinition()->getName()
        );
    }

    public function testGetDefinitionFail(): void
    {
        $this->expectException(NotFoundDefinitionInterface::class);

        $this->configurator->getDefinition(Bat::class);
    }

    public function testGetDefinitionWithFallback(): void
    {
        $res = $this->configurator->getDefinition(Bat::class, static fn (string $id) => new InvalidArgumentException('ID '.$id.' not registered.'));

        self::assertInstanceOf(InvalidArgumentException::class, $res);
        self::assertEquals('ID '.Bat::class.' not registered.', $res->getMessage());
    }
}
