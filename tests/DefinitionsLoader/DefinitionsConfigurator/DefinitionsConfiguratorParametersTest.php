<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\DefinitionsConfigurator;

use ArrayIterator;
use Kaspi\DiContainer\DefinitionsConfigurator;
use Kaspi\DiContainer\EventListener;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(DefinitionsConfigurator::class)]
#[CoversClass(EventListener::class)]
class DefinitionsConfiguratorParametersTest extends DefinitionsConfiguratorAbstract
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->parameters = new ArrayIterator(['foo' => 'bar']);
        $this->configurator = new DefinitionsConfigurator(
            $this->definitionsLoaderMock,
            $this->removedDefinitionsIds,
            $this->parameters,
            $this->configuratorContexts,
            $this->definitionsConfiguratorEvent,
        );
    }

    public function testHasAndRemoveParameter(): void
    {
        self::assertTrue($this->configurator->hasParameter('foo'));
        $this->configurator->removeParameter('foo');
        self::assertFalse($this->configurator->hasParameter('foo'));
    }
}
