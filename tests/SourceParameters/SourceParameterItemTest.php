<?php

declare(strict_types=1);

namespace Tests\SourceParameters;

use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Parameters\SourceParameterItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SourceParameterItem::class)]
class SourceParameterItemTest extends TestCase
{
    public function testGetUnresolvedParameter(): void
    {
        $this->expectException(ParameterExceptionInterface::class);
        $this->expectExceptionMessage('Container parameter "foo" is not resolve yet.');

        $item = new SourceParameterItem('foo', 'bar', false);
        $item->getResolved();
    }

    public function testGetResolvedParameter(): void
    {
        $item = new SourceParameterItem('foo', 'bar', false);
        $item->setResolved('baz');

        self::assertTrue($item->isResolved());
        self::assertEquals('baz', $item->getResolved());
    }

    public function testReset(): void
    {
        $item = new SourceParameterItem('foo', 'bar', false);
        $item->setResolved('baz');

        self::assertTrue($item->isResolved());
        self::assertEquals('baz', $item->getResolved());

        $item->reset();

        self::assertFalse($item->isResolved());

        $this->expectException(ParameterExceptionInterface::class);
        $item->getResolved();
    }
}
