<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\DefinitionsConfigurator;

use ArrayIterator;
use Kaspi\DiContainer\DefinitionsConfigurator;
use Kaspi\DiContainer\EventListener;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use PHPUnit\Framework\TestCase;

abstract class DefinitionsConfiguratorAbstract extends TestCase
{
    protected DefinitionsConfigurator $configurator;
    protected ArrayIterator $removedDefinitionsIds;
    protected ArrayIterator $parameters;
    protected ArrayIterator $configuratorContexts;
    protected EventListener $definitionsConfiguratorEvent;
    protected DefinitionsLoaderInterface $definitionsLoaderMock;

    protected function setUp(): void
    {
        $this->definitionsLoaderMock = $this->createMock(DefinitionsLoaderInterface::class);
        $this->removedDefinitionsIds = new ArrayIterator();
        $this->parameters = new ArrayIterator();
        $this->configuratorContexts = new ArrayIterator();
        $this->definitionsConfiguratorEvent = new EventListener();
    }

    protected function tearDown(): void
    {
        unset($this->configurator, $this->definitionsLoaderMock, $this->removedDefinitionsIds, $this->parameters, $this->configuratorContexts, $this->definitionsConfiguratorEvent);
    }
}
