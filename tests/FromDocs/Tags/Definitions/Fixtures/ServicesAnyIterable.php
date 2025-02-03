<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Definitions\Fixtures;

class ServicesAnyIterable
{
    public function __construct(public iterable $services) {}
}
