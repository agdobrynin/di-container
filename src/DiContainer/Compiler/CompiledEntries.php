<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Iterator;
use Kaspi\DiContainer\Exception\ContainerIdentifierExistException;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;

use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

final class CompiledEntries implements CompiledEntriesInterface
{
    /**
     * Array key is container identifier.
     *
     * @var array<non-empty-string, array{serviceMethod: non-empty-string, entry: CompiledEntryInterface}>
     */
    private array $entries = [];

    /**
     * @var array<non-empty-string, true>
     */
    private array $notFoundContainerIdentifiers = [];

    /**
     * @var array<non-empty-string, true>
     */
    private array $existServiceMethods = [];

    /**
     * @param non-empty-string $methodPrefix
     * @param non-empty-string $methodDefaultName
     */
    public function __construct(
        private readonly string $methodPrefix = 'resolve_',
        private readonly string $methodDefaultName = 'service',
    ) {}

    public function addNotFoudContainerId(string $id): void
    {
        $this->notFoundContainerIdentifiers[$id] = true;
    }

    public function setServiceMethod(string $id, CompiledEntryInterface $compiledEntry): void
    {
        if (isset($this->entries[$id])) {
            throw new ContainerIdentifierExistException(
                sprintf('Container identifier "%s" is already registered.', $id)
            );
        }

        $serviceSuffix = 0;
        $serviceMethodUnique = null;
        $serviceMethod = $this->convertContainerIdentifierToMethodName($id);

        while (isset($this->existServiceMethods[$serviceMethodUnique ?? $serviceMethod])) {
            ++$serviceSuffix;
            $serviceMethodUnique = $serviceMethod.$serviceSuffix;
        }

        $this->entries[$id] = [
            'serviceMethod' => $serviceMethodUnique ?? $serviceMethod,
            'entry' => $compiledEntry,
        ];

        $this->existServiceMethods[$this->entries[$id]['serviceMethod']] = true;
    }

    public function reset(): void
    {
        $this->entries = [];
        $this->existServiceMethods = [];
        $this->notFoundContainerIdentifiers = [];
    }

    public function getHasIdentifiers(): Iterator
    {
        foreach ($this->entries as $id => $entry) {
            if (!isset($this->notFoundContainerIdentifiers[$id])) {
                yield $id;
            }
        }
    }

    public function getContainerIdentifierMappedMethodResolve(): Iterator
    {
        foreach ($this->entries as $id => ['serviceMethod' => $serviceMethod]) {
            yield ['id' => $id, 'serviceMethod' => $serviceMethod];
        }
    }

    public function getCompiledEntries(): Iterator
    {
        foreach ($this->entries as $id => ['serviceMethod' => $serviceMethod, 'entry' => $entry]) {
            yield ['id' => $id, 'serviceMethod' => $serviceMethod, 'entry' => $entry];
        }
    }

    /**
     * @param non-empty-string $id
     *
     * @return non-empty-string
     */
    private function convertContainerIdentifierToMethodName(string $id): string
    {
        // Identifier may present as fully qualified class name. Take only class name.
        if (false !== ($pos = strrpos($id, '\\')) && isset($id[$pos + 1])) {
            $id = substr($id, $pos + 1);
        }

        /** @var string $name */
        $name = preg_replace_callback(
            '/([a-z])([A-Z])/',
            static fn (array $a) => $a[1].'_'.strtolower($a[2]),
            (string) preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '.', $id)
        );
        $name = strtolower(trim(str_replace('.', '_', $name), '_'));

        return 1 !== preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name)
            ? $this->methodPrefix.$this->methodDefaultName
            : $this->methodPrefix.$name;
    }
}
