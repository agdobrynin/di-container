<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName\Fixtures\Success;

final class TwoInOneOne
{
    public function __construct(private TwoInOneTow $two) {}

    public function aaa()
    {
        return $this->two->getToken();
    }
}

abstract class TwoInOneThree
{
    public function main(bool $b): string
    {
        return $b
            ? 'ok'
            : 'fail';
    }
}

interface SomeInterface
{
    public function aaa(): string;
}

final class TwoInOneTow
{
    public function __construct(private string $token) {}

    public function getToken(): string
    {
        return $this->token;
    }

    public static function make(string $token): self
    {
        return new self($token);
    }
}
