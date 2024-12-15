<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;

class DefinitionsLoader
{
    use DefinitionIdentifierTrait;

    private \ArrayIterator $configDefinitions;

    public function __construct()
    {
        $this->configDefinitions = new \ArrayIterator();
    }

    /**
     * @phan-suppress PhanUnreferencedPublicMethod
     */
    public function load(bool $overrideDefinitions, string ...$file): static
    {
        foreach ($file as $srcFile) {
            if (!\file_exists($srcFile) || !\is_readable($srcFile)) {
                throw new \InvalidArgumentException(\sprintf('The file "%s" does not exist or is not readable', $srcFile));
            }

            foreach ($this->getIterator($srcFile) as $identifier => $definition) {
                try {
                    $identifier = $this->getIdentifier($identifier, $definition);
                    $this->validateIdentifier($identifier);
                } catch (DiDefinitionException $e) {
                    throw new DiDefinitionException(
                        \sprintf('Invalid definition in file "%s". Reason: %s', $srcFile, $e->getMessage())
                    );
                }

                if (!$overrideDefinitions && $this->configDefinitions->offsetExists($identifier)) {
                    throw new ContainerAlreadyRegisteredException(
                        \sprintf('Invalid definition in file "%s". Reason: Definition with identifier "%s" is already registered', $srcFile, $identifier)
                    );
                }

                $this->configDefinitions->offsetSet($identifier, $definition);
            }
        }

        return $this;
    }

    /**
     * @phan-suppress PhanUnreferencedPublicMethod
     */
    public function definitions(): iterable
    {
        $this->configDefinitions->rewind();

        yield from $this->configDefinitions;
    }

    private function getIterator(string $srcFile): \Generator
    {
        \ob_start();
        $content = require $srcFile;
        \ob_get_clean(); // @phan-suppress-current-line PhanPluginUseReturnValueInternalKnown

        return match (true) {
            \is_iterable($content) => yield from $content,
            $content instanceof \Closure && \is_iterable($content()) => yield from $content(),
            default => throw new \InvalidArgumentException(
                \sprintf('File "%s" return not valid format. File must be return any iterable type or callback function with return type generator.', $srcFile)
            )
        };
    }
}
