<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;

trait ArgumentsInAttribute
{
    private array $arguments = [];

    public function getArguments(): array
    {
        \array_walk($this->arguments, static function (&$argument) {
            if (\is_string($argument)
                && \str_starts_with($argument, DiAttributeServiceInterface::IS_REFERENCE)) {
                $containerIdentifier = \substr($argument, \strlen(DiAttributeServiceInterface::IS_REFERENCE));
                $argument = new DiDefinitionReference($containerIdentifier);
            }
        });

        return $this->arguments;
    }
}
