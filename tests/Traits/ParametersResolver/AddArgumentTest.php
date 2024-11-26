<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class AddArgumentTest extends TestCase
{
    use ParametersResolverTrait;
    use PsrContainerTrait;

    public function testAddArgumentNonVariadicSuccess(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('iterator', []);

        $this->assertIsArray($this->resolveParameters());
    }

    public function testAddArgumentVariadicSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('iterator', [[], []]);

        $this->assertIsArray($this->resolveParameters());
    }

    public function testAddArgumentFailByName(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('a', []);

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Invalid input argument name "a"');

        $this->resolveParameters();
    }

    public function testAddArgumentsFailByName(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArguments([
            [],
        ]);

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Invalid input argument name "0"');

        $this->resolveParameters();
    }

    public function testAddArgumentFailByCount(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArguments(['iterator' => [], 'val' => 'value']);

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Too many input arguments');

        $this->resolveParameters();
    }

    public function testAddArgumentsWithoutNames(): void
    {
        $fn = static fn (string $value, SuperClass $class) => 'ok';
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArguments([
            'value' => diValue('value'),
            diAutowire(SuperClass::class), // 🚩 without array key as argument name
        ]);

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Invalid input argument name "0" at position #2');

        $this->resolveParameters();
    }

    public function testAddArgumentsSuccess(): void
    {
        $fn = static fn (iterable $iterator, ?string $value = null) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();
        $this->setUseAttribute(false);

        $this->addArguments([
            'iterator' => [],
        ]);

        $this->assertIsArray($this->resolveParameters());
    }

    public function testNoUserDefinedArgumentSuccess(): void
    {
        $fn = static fn (iterable $iterator = [], string $value = '') => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();
        $this->setUseAttribute(false);

        $this->addArguments([]);

        $this->assertIsArray($this->resolveParameters());
    }
}
