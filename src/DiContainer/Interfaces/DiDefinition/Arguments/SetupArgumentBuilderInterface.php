<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition\Arguments;

use Kaspi\DiContainer\Enum\SetupConfigureMethod;

interface SetupArgumentBuilderInterface
{
    public function argumentBuilder(): ArgumentBuilderInterface;

    public function setupType(): SetupConfigureMethod;
}
