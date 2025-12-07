<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

use function sprintf;

final class Compiler
{
    public function __construct(
        private readonly DiContainerInterface $container,
        private readonly string $containerClass,
    ) {}

    public function compile(): string
    {
        return sprintf('<?php
use Kaspi\DiContainer\Exception\{CallCircularDependencyException, NotFoundException};
use function array_keys;

class %s implements \Psr\Container\ContainerInterface
{
    /**
     * When resolving dependency check circular call.
     * @var array<non-empty-string, true> $resolvingContainerIds
     */
    private array $resolvingContainerIds = [];

    public function get(string $id): mixed
    {
        if (false !== $this->containerMap($id)) {
            try {
                if (isset($this->resolvingContainerIds[$id])) {
                    throw new CallCircularDependencyException(array_keys($this->resolvingContainerIds));
                }
                
                $this->resolvingContainerIds[$id] = true;

                return $this->$id();
            } finally {
                unset($this->resolvingContainerIds[$id]);
            }
        }
        
        throw new NotFoundException($id);
    }
    
    public function has(string $id): bool
    {
        return false !== $this->containerMap($id); 
    }
    
    private function containerMap(string $id): false|string
    {
        return match($id) {
            %s
            default => false,
        };
    }
}
        
', $this->containerClass);
    }
}
