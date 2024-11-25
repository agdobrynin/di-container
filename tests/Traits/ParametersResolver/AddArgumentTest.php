<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 *
 * @internal
 */
class AddArgumentTest extends TestCase
{
    use ParametersResolverTrait;
    use PsrContainerTrait;

    public function dataProviderAddArgumentSuccess(): \Generator
    {
        yield 'name #1' => ['name', 'aaaa'];

        yield 'name #2' => ['_4site', 'aaaa'];

        yield 'name #3' => ['täyte', 'aaaa'];

        yield 'name #4' => ['Terä', 'aaaa'];
    }

    /**
     * @dataProvider dataProviderAddArgumentSuccess
     */
    public function testAddArgumentSuccess(string $name, mixed $value): void
    {
        $this->addArgument($name, $value);

        $this->assertTrue(\array_key_exists($name, $this->arguments));
    }

    public function dataProviderAddArgumentFail(): \Generator
    {
        yield 'name #1' => ['    name    ', 'aaaa'];

        yield 'name #2' => ['4site', 'aaaa'];

        yield 'name #3' => ['Ter ', 'aaaa'];

        yield 'name #4' => [' Ter', 'aaaa'];
    }

    /**
     * @dataProvider dataProviderAddArgumentFail
     */
    public function testAddArgumentFail(string $name, mixed $value): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $this->addArgument($name, $value);
    }
}
