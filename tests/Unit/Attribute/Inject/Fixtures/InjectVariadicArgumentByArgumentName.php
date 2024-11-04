<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class InjectVariadicArgumentByArgumentName
{
    /**
     * @var array<int, array<int, string>>
     */
    public array $argNames;

    public function __construct(
        #[Inject]
        array ...$argName
    ) {
        $this->argNames = $argName;
    }
}
