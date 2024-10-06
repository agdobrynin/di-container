<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class DiContainerDefinition
{
    public function __construct(public string $id, public mixed $definition, public bool $shared, public array $arguments = []) {}

    public static function fromRawDefinition(string $id, mixed $rawDefinition, bool $sharedDefault): static
    {
        if ($rawDefinition instanceof \Closure) {
            return new DiContainerDefinition($id, $rawDefinition, $sharedDefault);
        }

        if (\is_array($rawDefinition)) {
            return new DiContainerDefinition(
                $id,
                $rawDefinition[0] ?? $id,
                $rawDefinition[DiContainerInterface::SHARED] ?? $sharedDefault,
                $rawDefinition[DiContainerInterface::ARGUMENTS] ?? []
            );
        }

        return new DiContainerDefinition($id, $rawDefinition, $sharedDefault);
    }
}
