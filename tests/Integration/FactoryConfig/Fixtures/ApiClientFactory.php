<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

final class ApiClientFactory
{
    public static function createApiV2(): ApiClientInterface
    {
        return new ApiClient();
    }
}
