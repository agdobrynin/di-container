<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular;

class ServiceUseOne implements ServiceUseInterface
{
    public function __construct(public ServiceUseTwo $serviceUseTwo) {}
}
