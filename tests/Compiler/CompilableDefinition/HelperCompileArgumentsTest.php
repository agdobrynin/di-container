<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompilableDefinition\CallableEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
#[CoversClass(\Kaspi\DiContainer\Helper::class)]
#[CoversClass(CallableEntry::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(ValueEntry::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(FinderClosureCode::class)]
class HelperCompileArgumentsTest extends TestCase
{
    public function testFallbackTransformer(): void
    {
        $args = [
            'foo',
            static fn (Foo $foo) => $foo->bar(),
        ];

        $transformer = new DiDefinitionTransformer(
            new FinderClosureCode()
        );

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->with(Foo::class)
            ->willReturn(true)
        ;

        $diContainerDefinitionsMock = $this->createMock(DiContainerDefinitionsInterface::class);
        $diContainerDefinitionsMock->method('getContainer')
            ->willReturn($containerMock)
        ;
        $diContainerDefinitionsMock->method('getDefinition')
            ->with(Foo::class)
            ->willReturn(new DiDefinitionAutowire(Foo::class))
        ;

        $expression = Helper::compileArguments(new CompiledEntry(), '$this', $args, $transformer, $diContainerDefinitionsMock);

        self::assertEquals(
            '(
  \'foo\',
  (static fn (\Tests\Compiler\CompilableDefinition\Foo $foo) => $foo->bar())(
  $this->get(\'Tests\\\Compiler\\\CompilableDefinition\\\Foo\'),
),
)',
            $expression
        );
    }
}

final class Foo
{
    public function bar() {}
}
