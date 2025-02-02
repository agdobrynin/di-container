<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular;

class ServiceUseTwo implements ServiceUseInterface
{
    public function __construct(public ServiceUseOne $serviceUseTwo) {}
}
