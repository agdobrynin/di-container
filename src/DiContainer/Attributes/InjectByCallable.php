<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class InjectByCallable
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }
}
