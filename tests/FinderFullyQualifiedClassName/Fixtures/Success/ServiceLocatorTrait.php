<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName\Fixtures\Success;

use Psr\Container\ContainerExceptionInterface;

trait ServiceLocatorTrait
{
    private array $factories;
    private array $loading = [];
    private array $providedTypes;

    /**
     * @param callable[] $factories
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }
    private function createCircularReferenceException(string $id, array $path): ContainerExceptionInterface
    {
        return new class(sprintf('Circular reference detected for service "%s", path: "%s".', $id, implode(' -> ', $path))) extends \RuntimeException implements ContainerExceptionInterface {
        };
    }
}
