<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

class SuperSrv implements MyInterface
{
    public function changeConfig(array $config): void {}
}
