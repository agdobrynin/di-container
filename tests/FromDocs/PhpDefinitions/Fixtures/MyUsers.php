<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

class MyUsers
{
    public function __construct(public array $users, public string $type) {}
}
