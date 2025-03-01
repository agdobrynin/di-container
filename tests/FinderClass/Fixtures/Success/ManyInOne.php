<?php

declare(strict_types=1);

namespace Tests\FinderClass\Fixtures\Success;

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
