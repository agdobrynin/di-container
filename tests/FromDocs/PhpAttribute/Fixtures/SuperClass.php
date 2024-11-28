<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

class SuperClass
{
    public function __construct(public MyInterface $my) {}
}
