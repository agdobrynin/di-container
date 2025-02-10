<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\TestFromIssues\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class RuleTaggedByInterface
{
    /**
     * @param RuleInterface[] $rules
     */
    public function __construct(
        #[TaggedAs(RuleInterface::class, priorityDefaultMethod: 'getPriority')]
        private iterable $rules
    ) {}

    /**
     * @throws \LogicException
     */
    public function validate(string $str): string
    {
        foreach ($this->rules as $rule) {
            $str = $rule->validate($str);
        }

        return $str;
    }
}
