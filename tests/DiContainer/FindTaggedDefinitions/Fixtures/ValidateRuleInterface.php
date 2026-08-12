<?php

declare(strict_types=1);

namespace Tests\DiContainer\FindTaggedDefinitions\Fixtures;

use InvalidArgumentException;

interface ValidateRuleInterface
{
    public function support(string $type): bool;

    /**
     * @throws InvalidArgumentException
     */
    public function validate(mixed $value): bool;
}
