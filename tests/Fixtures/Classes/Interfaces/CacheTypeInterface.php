<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes\Interfaces;

interface CacheTypeInterface
{
    public function driver(): string;
}
