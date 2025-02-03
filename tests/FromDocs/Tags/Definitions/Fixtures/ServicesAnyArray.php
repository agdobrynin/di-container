<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Definitions\Fixtures;

class ServicesAnyArray
{
    public function __construct(public array $services) {}
}
