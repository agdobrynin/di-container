<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class Invokable
{
    public function __invoke(Db $db): array
    {
        return $db->all();
    }
}
