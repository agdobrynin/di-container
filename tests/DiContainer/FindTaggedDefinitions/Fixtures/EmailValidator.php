<?php

declare(strict_types=1);

namespace Tests\DiContainer\FindTaggedDefinitions\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;

use function filter_var;

use const FILTER_VALIDATE_EMAIL;

#[Autowire(
    tags: [
        new Tag('tags.email_validator'),
    ]
)]
final class EmailValidator implements ValidateRuleInterface, SimplestValidatorInterface
{
    public const EMAIL_VALIDATOR = 'emailValidator';

    public function support(string $type): bool
    {
        return self::EMAIL_VALIDATOR === $type;
    }

    public function validate(mixed $value): bool
    {
        return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
