<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\FreezeTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(FreezeTrait::class)]
class FreezeTest extends TestCase
{
    #[TestWith(['bindArguments'])]
    #[TestWith(['bindTag'])]
    public function testFreeze(string $callMethod): void
    {
        $def = new DiDefinitionCallable('Tests\DiDefinition\DiDefinitionCallable\foo_bar');
        $def->bindTag('tags.foo');
        $def->bindArguments('qux');

        self::assertEquals(['tags.foo' => []], $def->getTags());
        self::assertEquals([0 => 'qux'], $def->exposeArgumentBuilder($this->createMock(DiContainerInterface::class))->getBindArguments());

        $def->freeze();

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot call \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable::'.$callMethod.'() on a frozen definition.');

        $def->{$callMethod}('x');
    }
}

function foo_bar(string $param): string
{
    return 'result: '.$param;
}
