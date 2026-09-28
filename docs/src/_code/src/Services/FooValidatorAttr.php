<?php
declare(strict_types=1);

// file: /app/src/Services/FooValidator.php //[!code highlight]

namespace App\Services;

use App\Interfaces\RuleInterface;
use Kaspi\DiContainer\Attributes\TaggedAs;//[!code ++]

final class FooValidator
{
    /**
     * @param iterable<RuleInterface> $rules
     */
    public function __construct(
        #[TaggedAs(RuleInterface::class)]//[!code ++]
        public readonly iterable $rules
    ) {}

    public function validate(mixed $input): mixed
    {
        foreach ($this->rules as $rule) {
            $input = $rule->validate($input);
        }

        return $input;
    }
}