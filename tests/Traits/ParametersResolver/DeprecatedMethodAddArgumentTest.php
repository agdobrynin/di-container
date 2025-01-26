<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class DeprecatedMethodAddArgumentTest extends TestCase
{
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    use DiContainerTrait;

    public function testAddArgumentNonVariadicSuccess(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('iterator', []);

        $this->assertEquals([], \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters)));
    }

    public function testAddArgumentVariadicSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('iterator', [[], []]);

        $this->assertEquals([[], []], \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters)));
    }

    public function testAddArgumentFailByName(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('a', []);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Invalid input argument name "a"');

        $this->resolveParameters($this->getBindArguments(), $reflectionParameters);
    }

    public function testAddArgumentFailByCount(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArguments(['iterator' => [], 'val' => 'value']);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Too many input arguments');

        $this->resolveParameters($this->getBindArguments(), $reflectionParameters);
    }

    public function testAddArgumentsWithoutNames(): void
    {
        $fn = static fn (string $value, SuperClass $class) => 'ok';
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->never())
            ->method('get')
        ;
        $this->setContainer($mockContainer);

        $this->addArguments([
            'value',
            diAutowire(SuperClass::class), // 🚩 without array key as argument name
        ]);

        $this->assertEquals('ok', \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters)));
    }

    public function testAddArgumentByIndex(): void
    {
        $fn = static fn (string $value, SuperClass $class) => 'ok';
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->never())
            ->method('get')
        ;
        $this->setContainer($mockContainer);

        $this->addArgument(0, 'value');
        $this->addArgument(1, diAutowire(SuperClass::class));

        $this->assertEquals('ok', \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters)));
    }

    public function testAddArgumentsSuccess(): void
    {
        $fn = static fn (iterable $iterator, ?string $value = null) => \array_merge((array) $iterator, [$value]);
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();
        $this->setUseAttribute(false);

        $this->addArguments([
            'iterator' => ['ok'],
        ]);

        $this->assertEquals(['ok', null], \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters)));
    }

    public function testNoUserDefinedArgumentSuccess(): void
    {
        $fn = static fn (array $array = [], string $value = 'app') => $array + [$value];
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();
        $this->setUseAttribute(false);

        $this->addArguments([])->getBindArguments();

        $this->assertEquals(['app'], \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters)));
    }
}
