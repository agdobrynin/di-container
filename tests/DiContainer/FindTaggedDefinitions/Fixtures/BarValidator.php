<?php

declare(strict_types=1);

namespace Tests\DiContainer\FindTaggedDefinitions\Fixtures;

final class BarValidator implements SimplestValidatorInterface
{
    public function support(string $type): bool
    {
        return true;
    }

    public function validate(mixed $value): bool
    {
        return true;
    }
}
