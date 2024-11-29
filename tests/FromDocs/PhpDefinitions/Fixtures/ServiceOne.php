<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

class ServiceOne
{
    public function __construct(private string $apiKey) {}

    public function getApiKey(): string
    {
        return $this->apiKey;
    }
}
