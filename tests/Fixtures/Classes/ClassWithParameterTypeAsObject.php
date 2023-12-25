<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

readonly class ClassWithParameterTypeAsObject
{
    public function __construct(public object $asObject) {}
}
