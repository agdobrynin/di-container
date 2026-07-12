<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ObjectEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;
use Kaspi\DiContainer\ObjectResetters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\BazResetter;
use Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo;

/**
 * @internal
 */
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(ObjectEntry::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(\Kaspi\DiContainer\Helper::class)]
class CompiledObjectResettersTest extends TestCase
{
    protected DiContainerDefinitionsInterface $diContainerDefinitionsMock;

    protected function setUp(): void
    {
        parent::setUp();
        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->with(ContainerInterface::class)
            ->willReturn(true)
        ;

        $diContainerDefinitionsMock = $this->createMock(DiContainerDefinitionsInterface::class);
        $diContainerDefinitionsMock->method('getContainer')
            ->willReturn($containerMock)
        ;

        $this->diContainerDefinitionsMock = $diContainerDefinitionsMock;
    }

    protected function tearDown(): void
    {
        unset($this->diContainerDefinitionsMock);
    }

    public function testSetupMethodArgumentNotIterable(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('The first argument for Kaspi\DiContainer\ObjectResetters::setup() should be `iterable` type.');

        $definition = (new DiDefinitionAutowire(ObjectResetters::class))
            ->setup('setup', [new stdClass()])
        ;

        (new ObjectEntry(
            $definition,
            $this->diContainerDefinitionsMock,
            new DiDefinitionTransformer($this->createMock(FinderClosureCodeInterface::class)),
        ))->compile('$this');
    }

    public function testSetupMethodContainsUnsupportedTypeResetter(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('The resetter for container identifier \'service.foo\' type should be is `callable` or `string`.');

        $definition = (new DiDefinitionAutowire(ObjectResetters::class))
            ->setup('setup', [['service.foo' => new stdClass()]])
        ;

        (new ObjectEntry(
            $definition,
            $this->diContainerDefinitionsMock,
            new DiDefinitionTransformer($this->createMock(FinderClosureCodeInterface::class)),
        ))->compile('$this');
    }

    public function testCompileResetters(): void
    {
        $definition = (new DiDefinitionAutowire(ObjectResetters::class))
            ->setup('setup', [
                [
                    'service.foo' => static function (Foo $foo): void {},
                    'service.baz' => [BazResetter::class, 'doReset'],
                    'service.bar' => 'reset',
                ],
            ])
        ;

        $finderClosureCode = $this->createMock(FinderClosureCodeInterface::class);
        $finderClosureCode->method('getCode')
            ->willReturn('static function (\Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo $foo): void {}')
        ;

        $compiledEntry = (new ObjectEntry(
            $definition,
            $this->diContainerDefinitionsMock,
            new DiDefinitionTransformer($finderClosureCode),
        ))->compile('$this');

        self::assertEquals('$object', $compiledEntry->getExpression());

        self::assertCount(2, $compiledEntry->getStatements());

        self::assertEquals(
            '$object = new \Kaspi\DiContainer\ObjectResetters(
  $this->get(\'Psr\\\Container\\\ContainerInterface\'),
)',
            $compiledEntry->getStatements()[0]
        );
        self::assertEquals(
            '$object->setup([
  \'service.foo\' => static function (\Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo $foo): void {},
  \'service.baz\' => [\'\\\Tests\\\Compiler\\\CompilableDefinition\\\ObjectEntry\\\Fixtures\\\BazResetter\', \'doReset\'],
  \'service.bar\' => \'reset\',
])',
            $compiledEntry->getStatements()[1]
        );
    }
}
