<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Finder\FinderClass;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClassInterface;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Psr\Container\ContainerExceptionInterface;

final class DefinitionsLoader implements DefinitionsLoaderInterface
{
    use DefinitionIdentifierTrait;

    private \ArrayIterator $configDefinitions;

    /**
     * @var array<non-empty-string, FinderClassInterface>
     */
    private array $import;

    public function __construct()
    {
        $this->configDefinitions = new \ArrayIterator();
    }

    public function load(bool $overrideDefinitions, string ...$file): static
    {
        foreach ($file as $srcFile) {
            if (!\file_exists($srcFile) || !\is_readable($srcFile)) {
                throw new \InvalidArgumentException(\sprintf('The file "%s" does not exist or is not readable', $srcFile));
            }

            try {
                $this->addDefinitions($overrideDefinitions, $this->getIteratorFromFile($srcFile));
                unset($srcFile);
            } catch (ContainerExceptionInterface|DiDefinitionExceptionInterface $e) {
                throw new ContainerException(
                    \sprintf('Invalid definition in file "%s". Reason: %s', $srcFile, $e->getMessage())
                );
            }
        }

        return $this;
    }

    public function addDefinitions(bool $overrideDefinitions, iterable $definitions): static
    {
        $itemCount = 0;

        foreach ($definitions as $identifier => $definition) {
            try {
                /** @var class-string|non-empty-string $identifier */
                $identifier = $this->getIdentifier($identifier, $definition);
            } catch (DiDefinitionExceptionInterface $e) {
                throw new DiDefinitionException(
                    message: \sprintf('%s Item position #%d.', $e->getMessage(), $itemCount),
                    previous: $e
                );
            }

            if (!$overrideDefinitions && $this->configDefinitions->offsetExists($identifier)) {
                throw new ContainerAlreadyRegisteredException(
                    \sprintf(
                        'Definition with identifier "%s" is already registered. Item position #%d.',
                        $identifier,
                        $itemCount
                    )
                );
            }

            $this->configDefinitions->offsetSet($identifier, $definition);
            ++$itemCount;
        }

        return $this;
    }

    public function definitions(): iterable
    {
        $this->configDefinitions->rewind();

        if (isset($this->import)) {
            // @todo implement import definitions from files.
            throw new \LogicException('Import class not implemented yet.');
        }

        yield from $this->configDefinitions; // @phpstan-ignore generator.keyType
    }

    public function import(string $namespace, string $src, array $excludeFilesRegExpPattern = []): static
    {
        $this->import[$namespace] ??= $this->import[$namespace] = (new FinderClass(
            $namespace,
            (new FinderFile($src, $excludeFilesRegExpPattern))->getFiles()
        ));

        return $this;
    }

    private function getIteratorFromFile(string $srcFile): \Generator
    {
        \ob_start();
        $content = require $srcFile;
        \ob_get_clean();

        return match (true) {
            \is_iterable($content) => yield from $content,
            $content instanceof \Closure && \is_iterable($content()) => yield from $content(),
            default => throw new \InvalidArgumentException(
                \sprintf('File "%s" return not valid format. File must be use "return" keyword, and return any iterable type or callback function using "yield" keyword.', $srcFile)
            )
        };
    }
}
