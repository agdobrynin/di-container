<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Psr\Container\ContainerInterface;

class EasyContainer implements ContainerInterface
{
    public array $instance = [];

    public function get(string $id)
    {
        return $this->instance[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->instance[$id]);
    }
}
