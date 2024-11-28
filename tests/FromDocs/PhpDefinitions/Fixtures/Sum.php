<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

class Sum implements SumInterface
{
    public function __construct(private int $init) {}

    public function getInit(): int
    {
        return $this->init;
    }
}
