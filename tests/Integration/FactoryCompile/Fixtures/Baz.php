<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryCompile\Fixtures;

final class Baz
{
    public string $str;

    public function withStr(string $str): self
    {
        $this->str = $str;

        return $this;
    }
}
