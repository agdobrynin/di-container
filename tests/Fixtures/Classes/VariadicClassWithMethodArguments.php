<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Kaspi\DiContainer\Attributes\Inject;
use Tests\Fixtures\Classes\Interfaces\VariadicParameterInterface;

class VariadicClassWithMethodArguments
{
    public function __construct(
        #[Inject(arguments: ['array' => '@config.medals'])]
        public \ArrayIterator $iterator
    ) {}

    public function getParameters(VariadicParameterInterface ...$parameter): array
    {
        return \array_merge($parameter, $this->iterator->getArrayCopy());
    }
}
