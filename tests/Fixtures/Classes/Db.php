<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Tests\Fixtures\Classes\Interfaces\CacheTypeInterface;

readonly class Db
{
    public function __construct(
        protected array $data,
        public ?string $store = null,
        public ?CacheTypeInterface $cache = null,
    ) {}

    public function all(): array
    {
        return $this->data;
    }
}
