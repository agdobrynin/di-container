<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Psr\Container\ContainerInterface;

/**
 * @template T of object
 */
class EasyContainer implements ContainerInterface
{
    public array $instance = [];

    /**
     * @param class-string<T> $id
     *
     * @return T
     */
    public function get(string $id)
    {
        return $this->instance[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->instance[$id]);
    }
}
