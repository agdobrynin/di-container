<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DTO;

use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\SetupArgumentBuilderInterface;

final class SetupArgumentBuilder implements SetupArgumentBuilderInterface
{
    public function __construct(private readonly ArgumentBuilderInterface $argumentBuilder, private readonly SetupConfigureMethod $setupType) {}

    public function argumentBuilder(): ArgumentBuilderInterface
    {
        return $this->argumentBuilder;
    }

    public function setupType(): SetupConfigureMethod
    {
        return $this->setupType;
    }
}
