<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\DefinitionsConfigurator;

use ArrayIterator;
use Kaspi\DiContainer\DefinitionsConfigurator;
use Kaspi\DiContainer\EventListener;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(DefinitionsConfigurator::class)]
#[CoversClass(EventListener::class)]
class DefinitionsConfiguratorContextTest extends DefinitionsConfiguratorAbstract
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configuratorContexts = new ArrayIterator([
            'foo' => 'bar',
        ]);

        $this->configurator = new DefinitionsConfigurator(
            $this->definitionsLoaderMock,
            $this->removedDefinitionsIds,
            $this->parameters,
            $this->configuratorContexts,
            $this->definitionsConfiguratorEvent,
        );
    }

    public function testGetContextSuccess(): void
    {
        self::assertEquals('bar', $this->configurator->getContext('foo'));
    }

    public function testGetContextException(): void
    {
        $this->expectException(DefinitionsLoaderException::class);
        $this->expectExceptionMessage('The context name \'fuz\' does not exist.');

        $this->configurator->getContext('fuz');
    }

    public function testGetContextFallBack(): void
    {
        $res = $this->configurator->getContext('fuz', static fn ($name) => 'The "fuz" not valid!');
        self::assertEquals('The "fuz" not valid!', $res);
    }
}
