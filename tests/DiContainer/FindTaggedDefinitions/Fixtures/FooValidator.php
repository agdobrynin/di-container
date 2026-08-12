<?php

declare(strict_types=1);

namespace Tests\DiContainer\FindTaggedDefinitions\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;

#[Autowire(
    tags: [
        new Tag(ValidateRuleInterface::class),
    ]
)]
final class FooValidator implements SimplestValidatorInterface
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
