<?php

declare(strict_types=1);

namespace Tests\FromDocs\Call\Fixtires;

class PostController
{
    public function __construct(private ServiceOne $serviceOne) {}

    public function store(string $name): string
    {
        $this->serviceOne->save($name);

        return 'The name '.$name.' saved!';
    }
}
