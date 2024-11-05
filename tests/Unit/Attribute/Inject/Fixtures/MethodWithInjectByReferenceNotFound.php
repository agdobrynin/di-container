<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class MethodWithInjectByReferenceNotFound
{
    public function __construct() {}

    public function rulesInvoke(
        #[Inject('@rules.text.strip_tags', arguments: ['rule' => 'address'])]
        RuleInterface $rule,
    ): string {}
}
