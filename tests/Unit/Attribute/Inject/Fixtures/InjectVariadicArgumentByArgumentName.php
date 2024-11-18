<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByReference;

class InjectVariadicArgumentByArgumentName
{
    /**
     * @var array<int, array<int, string>>
     */
    public array $argNames;

    public function __construct(
        #[InjectByReference('welcome.variadic_param')]
        array ...$argName
    ) {
        $this->argNames = $argName;
    }
}
