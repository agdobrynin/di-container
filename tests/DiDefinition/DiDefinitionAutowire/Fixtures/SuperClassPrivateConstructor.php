<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

class SuperClassPrivateConstructor implements SuperInterface
{
    private function __construct() {}
}
