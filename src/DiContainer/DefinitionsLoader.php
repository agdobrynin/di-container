<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;

class DefinitionsLoader
{
    use DefinitionIdentifierTrait;

    private \ArrayIterator $iterator;

    public function __construct()
    {
        $this->iterator = new \ArrayIterator();
    }

    /**
     * @phan-suppress PhanUnreferencedPublicMethod
     */
    public function load(bool $overrideDefinitions, string ...$file): void
    {
        foreach ($file as $srcFile) {
            if (!\file_exists($srcFile) || !\is_readable($srcFile)) {
                throw new \InvalidArgumentException(\sprintf('File "%s" does not exist or is not readable', $srcFile));
            }

            $content = require $srcFile;

            $fnSrc = static fn () => match (true) {
                $content instanceof \Closure => yield from $content(),
                \is_array($content) => yield from $content,
                default => throw new \InvalidArgumentException(
                    \sprintf('File "%s" return not valid format. File must be return an array or callback function with generator type.', $srcFile)
                ),
            };

            foreach ($fnSrc() as $identifier => $definition) {
                try {
                    $identifier = $this->getIdentifier($identifier, $definition);
                    $this->validateIdentifier($identifier);
                } catch (DiDefinitionException $e) {
                    throw new DiDefinitionException(
                        \sprintf('Invalid definition in file "%s". Reason: %s', $srcFile, $e->getMessage())
                    );
                }

                if (!$overrideDefinitions && $this->iterator->offsetExists($identifier)) {
                    throw new ContainerAlreadyRegisteredException(
                        \sprintf('Invalid definition in file "%s". Reason: Definition with identifier "%s" is already registered', $srcFile, $identifier)
                    );
                }

                $this->iterator->offsetSet($identifier, $definition);
            }
        }
    }

    /**
     * @phan-suppress PhanUnreferencedPublicMethod
     */
    public function definitions(): iterable
    {
        yield from $this->iterator;
    }
}
