<?php

declare(strict_types=1);

namespace Tests\DiContainer\FindTaggedDefinitions\Fixtures;

use InvalidArgumentException;

use function is_string;
use function preg_match_all;
use function sprintf;
use function strlen;

final class PasswordValidator implements ValidateRuleInterface, SimplestValidatorInterface
{
    public const PASSWORD_VALIDATOR = 'passwordValidator';
    private const MIN_PASSWORD_LENGTH = 8;
    private const CONTAINS_MIN_DIGITS = 2;
    private const CONTAINS_MIN_UPPER_CHARS = 2;

    public function support(string $type): bool
    {
        return self::PASSWORD_VALIDATOR === $type;
    }

    public function validate(mixed $value): bool
    {
        if (is_string($value) && strlen($value) < self::MIN_PASSWORD_LENGTH) {
            return self::CONTAINS_MIN_UPPER_CHARS >= preg_match_all('/\d/', $value)
                && self::CONTAINS_MIN_UPPER_CHARS >= preg_match_all('/[A-Z]/', $value);
        }

        throw new InvalidArgumentException(
            sprintf('The password must be a string. The minimum length is %d characters; it must contain %d uppercase characters and %d digits.', self::MIN_PASSWORD_LENGTH, self::CONTAINS_MIN_UPPER_CHARS, self::CONTAINS_MIN_DIGITS)
        );
    }
}
