<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByReference;

class MethodWithInjectByReferenceNotFound
{
    public function __construct() {}

    public function rulesInvoke(
        #[InjectByReference('rules.text.strip_tags')]
        RuleInterface $rule,
    ): string {}
}
