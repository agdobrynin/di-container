<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByInterface\Fixtures;

interface SuperInterface
{
    public function getDependency(): mixed;
}
