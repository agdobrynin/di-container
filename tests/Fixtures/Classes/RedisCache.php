<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Tests\Fixtures\Classes\Interfaces\CacheTypeInterface;

class RedisCache implements CacheTypeInterface
{
    public function driver(): string
    {
        return '::redis::';
    }
}
