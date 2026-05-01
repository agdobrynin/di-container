<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionParameter;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Parameter;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameterWithContextAbstract;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Exception\ParameterNotFoundException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

use function Kaspi\DiContainer\diParameter;

/**
 * @internal
 */
#[CoversFunction('Kaspi\DiContainer\diParameter')]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiDefinitionParameter::class)]
#[CoversClass(Parameter::class)]
#[CoversClass(Helper::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(ParameterNotFoundException::class)]
#[CoversClass(DiDefinitionParameterWithContextAbstract::class)]
class ResolveParameterTest extends TestCase
{
    private DiContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = self::createMock(DiContainerInterface::class);
    }

    protected function tearDown(): void
    {
        unset($this->container);
    }

    public function testResolveParameterByIndexArgumentWithoutDefinitionName(): void
    {
        $fn = static fn (string $adminEmail) => null;

        $config = self::createMock(DiContainerConfigInterface::class);
        $config->method('isUseAttribute')
            ->willReturn(false)
        ;

        $this->container->method('getConfig')
            ->willReturn($config)
        ;

        $sourceParams = self::createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->with('adminEmail')
            ->willReturn('foo@example.com')
        ;

        $this->container->method('parameters')
            ->willReturn($sourceParams)
        ;

        $bindArgs = [
            0 => diParameter(),
        ];

        $ab = new ArgumentBuilder($bindArgs, new ReflectionFunction($fn), $this->container);
        $res = ArgumentResolver::resolve($ab, $this->container);

        self::assertEquals(['foo@example.com'], $res);
    }

    public function testResolveParameterByNamedArgumentWithoutDefinitionName(): void
    {
        $fn = static fn (string $adminEmail, string ...$lines) => null;

        $config = self::createMock(DiContainerConfigInterface::class);
        $config->method('isUseAttribute')
            ->willReturn(false)
        ;

        $this->container->method('getConfig')
            ->willReturn($config)
        ;

        $sourceParams = self::createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->willReturnMap([
                ['adminEmail', 'foo@example.com'],
                ['lines', 'hello'],
                ['line_second', 'world'],
            ])
        ;

        $this->container->method('parameters')->willReturn($sourceParams);

        $bindArgs = [
            'adminEmail' => diParameter(),
            'lines' => diParameter(),
            'line_2' => diParameter('line_second'),
        ];

        $ab = new ArgumentBuilder($bindArgs, new ReflectionFunction($fn), $this->container);
        $res = ArgumentResolver::resolve($ab, $this->container);

        self::assertEquals(
            [
                0 => 'foo@example.com',
                'lines' => 'hello',
                'line_2' => 'world',
            ],
            $res
        );
    }

    public function testResolveParameterByAttributeWithoutDefinitionName(): void
    {
        $fn = static fn (#[Parameter] string $adminEmail) => null;

        $sourceParams = self::createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->willReturnMap([
                ['adminEmail', 'foo@example.com'],
            ])
        ;

        $config = self::createMock(DiContainerConfigInterface::class);
        $config->method('isUseAttribute')
            ->willReturn(true)
        ;

        $this->container->method('getConfig')
            ->willReturn($config)
        ;

        $this->container->method('parameters')->willReturn($sourceParams);

        $ab = new ArgumentBuilder([], new ReflectionFunction($fn), $this->container);
        $res = ArgumentResolver::resolve($ab, $this->container);

        self::assertEquals(['foo@example.com'], $res);
    }

    public function testResolveParameterByIsOutOfBoundsIndexArgumentWithoutDefinitionName(): void
    {
        $fn = static fn (string $adminEmail, string ...$ccEmails) => null;

        $config = self::createMock(DiContainerConfigInterface::class);
        $config->method('isUseAttribute')
            ->willReturn(false)
        ;

        $this->container->method('getConfig')
            ->willReturn($config)
        ;

        $sourceParams = self::createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->willReturnMap([
                ['adminEmail', 'foo@example.com'],
                ['ccEmails', 'bar@example.com'],
                ['emails.cc_email', 'baz@example.com'],
            ])
        ;

        $this->container->method('parameters')
            ->willReturn($sourceParams)
        ;

        $bindArgs = [
            0 => diParameter(),
            1 => diParameter(),
            'emails_cc_email' => diParameter('emails.cc_email'),
        ];

        $ab = new ArgumentBuilder($bindArgs, new ReflectionFunction($fn), $this->container);
        $res = ArgumentResolver::resolve($ab, $this->container);

        self::assertEquals(
            [
                0 => 'foo@example.com',
                1 => 'bar@example.com',
                'emails_cc_email' => 'baz@example.com',
            ],
            $res
        );
    }

    public function testResolveParameterByAttributeWithoutDefinitionAndWithDefinition(): void
    {
        $fn = static fn (
            #[Parameter]
            string $adminEmail,
            #[Parameter]
            #[Parameter('emails.cc_email')]
            string ...$ccEmails
        ) => null;

        $config = self::createMock(DiContainerConfigInterface::class);
        $config->method('isUseAttribute')
            ->willReturn(true)
        ;

        $this->container->method('getConfig')
            ->willReturn($config)
        ;

        $sourceParams = self::createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->willReturnMap([
                ['adminEmail', 'foo@example.com'],
                ['ccEmails', 'bar@example.com'],
                ['emails.cc_email', 'baz@example.com'],
            ])
        ;

        $this->container->method('parameters')
            ->willReturn($sourceParams)
        ;

        $ab = new ArgumentBuilder([], new ReflectionFunction($fn), $this->container);
        $res = ArgumentResolver::resolve($ab, $this->container);
        self::assertEquals(
            [
                0 => 'foo@example.com',
                1 => 'bar@example.com',
                2 => 'baz@example.com',
            ],
            $res
        );
    }

    public function testResolveParameterByNamedArgumentHasException(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $fn = static fn (string ...$foo) => null;

        $config = self::createMock(DiContainerConfigInterface::class);
        $config->method('isUseAttribute')
            ->willReturn(false)
        ;

        $this->container->method('getConfig')
            ->willReturn($config)
        ;

        $sourceParams = self::createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->willReturnCallback(static function (string $id) {
                if ('foo' === $id) {
                    return 'bar';
                }

                throw new ParameterNotFoundException();
            })
        ;

        $this->container->method('parameters')->willReturn($sourceParams);

        $bindArgs = [
            'foo' => diParameter(),
            'foo_2' => diParameter(),
        ];

        $ab = new ArgumentBuilder($bindArgs, new ReflectionFunction($fn), $this->container);

        ArgumentResolver::resolve($ab, $this->container);
    }

    #[DataProvider('provideResolveUsingOrderingNameAndContext')]
    public function testResolveUsingOrderingNameAndContext(DiDefinitionParameter $param, string $expectParamName, ?string $resolveContext): void
    {
        $parameters = self::createMock(SourceParametersMutableInterface::class);
        $parameters->expects(self::once())
            ->method('get')
            ->with($expectParamName) // This important
        ;

        $container = self::createMock(DiContainerInterface::class);
        $container->method('parameters')->willReturn($parameters);

        $param->resolve($container, $resolveContext);
    }

    public static function provideResolveUsingOrderingNameAndContext(): Generator
    {
        yield 'set name in constructor' => [
            new DiDefinitionParameter('foo.constructor'),
            'foo.constructor',
            null,
        ];

        yield 'set name via `setContext()`' => [
            (new DiDefinitionParameter())->setContext('foo.set_context'),
            'foo.set_context',
            null,
        ];

        yield 'set name via `resolve()`' => [
            new DiDefinitionParameter(),
            'foo.set_in_resolve',
            'foo.set_in_resolve',
        ];
    }
}
