<?php
declare(strict_types=1);

// file: /app/src/Services/FooValidator.php //[!code highlight]

namespace App\Services;

use App\Interfaces\RuleInterface;

final class FooValidator
{
    /**
     * @param iterable<RuleInterface> $rules
     */
    public function __construct(public readonly iterable $rules) {}

    public function validate(mixed $input): mixed
    {
        foreach ($this->rules as $rule) {
            $input = $rule->validate($input);
        }

        return $input;
    }
}