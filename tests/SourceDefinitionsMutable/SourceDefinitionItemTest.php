<?php

declare(strict_types=1);

namespace Tests\SourceDefinitionsMutable;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCallableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionValueInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\SourceDefinitions\SourceDefinitionItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
#[CoversClass(SourceDefinitionItem::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionValue::class)]
class SourceDefinitionItemTest extends TestCase
{
    #[TestWith(['', []])]
    #[TestWith(['', new DiDefinitionValue([])])]
    #[TestWith([10, new DiDefinitionCallable('\log')])]
    public function testInvalidContainerIdentifier(int|string $srcId, mixed $definition): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);
        $this->expectExceptionMessage('Definition identifier must be a non-empty string');

        new SourceDefinitionItem($srcId, $definition, false);
    }

    #[TestWith(['foo', [], 'foo', DiDefinitionValueInterface::class])]
    #[TestWith(['bar', new DiDefinitionValue([]), 'bar', DiDefinitionValueInterface::class])]
    #[TestWith([10, new DiDefinitionAutowire(Foo::class), Foo::class, DiDefinitionAutowireInterface::class])]
    #[TestWith(['foo', '\log', 'foo', DiDefinitionCallableInterface::class])]
    #[TestWith(['foo', [Foo::class, 'do'], 'foo', DiDefinitionCallableInterface::class])]
    public function testSuccessCreate(int|string $srcId, mixed $definition, string $expectId, string $expectInstanceOf): void
    {
        $src = new SourceDefinitionItem($srcId, $definition, false);

        self::assertEquals($expectId, $src->containerIdentifier);
        self::assertInstanceOf($expectInstanceOf, $src->diDefinition);
    }

    public function testOtherParams(): void
    {
        $src = new SourceDefinitionItem('foo', [], true);

        self::assertTrue($src->isMutable);
        self::assertFalse($src->isReplaceRemovedId);
        self::assertEquals('foo', $src->containerIdentifier);
        self::assertInstanceOf(DiDefinitionValueInterface::class, $src->diDefinition);

        $src->isReplaceRemovedId = true;

        self::assertTrue($src->isReplaceRemovedId);
    }
}

final class Foo
{
    public static function do(): string
    {
        return 'foo';
    }
}
