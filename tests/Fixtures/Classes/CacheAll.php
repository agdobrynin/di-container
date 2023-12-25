<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

readonly class CacheAll
{
    public function __construct(
        public FileCache $fileCache,
        public RedisCache $redisCache
    ) {}
}
