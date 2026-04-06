<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionParameter;

use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Exception\ParameterException;
use Kaspi\DiContainer\Exception\ParameterNotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @internal
 */
#[CoversClass(DiDefinitionParameter::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(ParameterNotFoundException::class)]
class DiDefinitionParameterTest extends TestCase
{
    #[TestWith(['name' => '', 'expect' => ''])]
    #[TestWith(['name' => 'foo', 'expect' => 'foo'])]
    public function testParameterName(string $name, string $expect): void
    {
        self::assertEquals($expect, (new DiDefinitionParameter($name))->getDefinition());
    }

    public function testResolveExceptionEmptyNameEmptyContext(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Parameter name must be non-empty string');

        $container = self::createMock(DiContainerInterface::class);
        (new DiDefinitionParameter(''))->resolve($container);
    }

    public function testResolveByContext(): void
    {
        $context = new ReflectionParameter(static fn (int $port) => $port, 0);

        $parameters = self::createMock(SourceParametersMutableInterface::class);
        $parameters->method('get')->with('port')->willReturn(8080);
        $container = self::createMock(DiContainerInterface::class);
        $container->method('parameters')->willReturn($parameters);

        $paramValue = (new DiDefinitionParameter(''))->resolve($container, $context);

        self::assertEquals(8080, $paramValue);
    }

    public function testResolveExceptionFromSourceParametersParameterException(): void
    {
        $this->expectException(ParameterExceptionInterface::class);

        $parameters = self::createMock(SourceParametersMutableInterface::class);
        $parameters->method('get')->with('foo.bar')->willThrowException(new ParameterException());

        $container = self::createMock(DiContainerInterface::class);
        $container->method('parameters')->willReturn($parameters);

        (new DiDefinitionParameter('foo.bar'))->resolve($container);
    }

    public function testResolveExceptionFromSourceParametersParameterNotFound(): void
    {
        $this->expectException(ParameterNotFoundExceptionInterface::class);

        $parameters = self::createMock(SourceParametersMutableInterface::class);
        $parameters->method('get')->with('foo.bar')->willThrowException(new ParameterNotFoundException());

        $container = self::createMock(DiContainerInterface::class);
        $container->method('parameters')->willReturn($parameters);

        (new DiDefinitionParameter('foo.bar'))->resolve($container);
    }
}
