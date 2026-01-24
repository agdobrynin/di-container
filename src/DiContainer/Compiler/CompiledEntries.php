<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Iterator;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;

final class CompiledEntries implements CompiledEntriesInterface
{
    /**
     * Array key internal getter method name.
     * Each method name is converted to lowercase.
     *
     *      [
     *          'resolve_service_one' => [
     *              0 => 'App\\Services\\ServiceOne',
     *              1 => $compiledEntry,
     *          ],
     *      ]
     *
     * The value of array element has two items:
     * - index 0 – container identifier.
     * - index 1 – compiled entry.
     *
     * @var array<non-empty-string, array{0: non-empty-string, 1:CompiledEntryInterface}>
     */
    private array $mapServiceMethodToContainerId = [];

    /**
     * @var array<non-empty-string, true>
     */
    private array $notFoundContainerIdentifiers = [];

    public function addNotFoudContainerId(string $id): void
    {
        $this->notFoundContainerIdentifiers[$id] = true;
    }

    public function hasServiceMethod(string $serviceMethod): bool
    {
        return isset($this->mapServiceMethodToContainerId[$serviceMethod]);
    }

    public function setServiceMethod(string $serviceMethod, string $containerIdentifier, CompiledEntryInterface $compiledEntry): void
    {
        $serviceSuffix = 0;
        $serviceMethodUnique = null;

        while ($this->hasServiceMethod($serviceMethodUnique ?? $serviceMethod)) {
            ++$serviceSuffix;
            $serviceMethodUnique = $serviceMethod.$serviceSuffix;
        }

        $this->mapServiceMethodToContainerId[$serviceMethodUnique ?? $serviceMethod] = [$containerIdentifier, $compiledEntry];
    }

    public function reset(): void
    {
        $this->mapServiceMethodToContainerId = [];
        $this->notFoundContainerIdentifiers = [];
    }

    public function getHasIdentifiers(): Iterator
    {
        foreach ($this->mapServiceMethodToContainerId as [$id]) {
            if (!isset($this->notFoundContainerIdentifiers[$id])) {
                yield $id;
            }
        }
    }

    public function getContainerIdentifierMappedMethodResolve(): Iterator
    {
        foreach ($this->mapServiceMethodToContainerId as $method => [$id]) {
            yield ['id' => $id, 'serviceMethod' => $method];
        }
    }

    public function getCompiledEntries(): Iterator
    {
        foreach ($this->mapServiceMethodToContainerId as $method => [$id, $entry]) {
            yield ['id' => $id, 'serviceMethod' => $method, 'entry' => $entry];
        }
    }
}
