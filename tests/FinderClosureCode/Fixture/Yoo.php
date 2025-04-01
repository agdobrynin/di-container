<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

use Closure;

final class Yoo extends Y {
    public const YOO = 'I am class '.__CLASS__;
    public function __construct(private string $name) {}

    public function getClosureFunction(): Closure
    {
        return function () {
            return $this->name;
        };
    }

    public function getClosureFn(): Closure
    {
        return static fn () => $this->name;
    }

    public function getClosureFnWithParent(): Closure
    {
        return static fn() => PARENT::getClosure();
    }

    public function getClosureUsingSelf(): Closure
    {
        return static function () {
            return new self('a');
        };
    }

    public function getClosureUsingStatic(): Closure
    {
        return static function () {
            return new static(parent::make());
        };
    }

    public function getClosureWithParent(): Closure
    {
        return static function () {
            $a = __NAMESPACE__;
            $b = __CLASS__;
            return (new parent())->getClosure();
        };
    }
}
