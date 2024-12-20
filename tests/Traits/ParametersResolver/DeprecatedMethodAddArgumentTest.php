<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class DeprecatedMethodAddArgumentTest extends TestCase
{
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    use PsrContainerTrait;

    public function testAddArgumentNonVariadicSuccess(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('iterator', []);
        $this->arguments = $this->getBindArguments();

        $this->assertEquals([], \call_user_func_array($fn, $this->resolveParameters()));
    }

    public function testAddArgumentVariadicSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('iterator', [[], []]);
        $this->arguments = $this->getBindArguments();

        $this->assertEquals([[], []], \call_user_func_array($fn, $this->resolveParameters()));
    }

    public function testAddArgumentFailByName(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArgument('a', []);
        $this->arguments = $this->getBindArguments();

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Invalid input argument name "a"');

        $this->resolveParameters();
    }

    public function testAddArgumentFailByCount(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->addArguments(['iterator' => [], 'val' => 'value']);
        $this->arguments = $this->getBindArguments();

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Too many input arguments');

        $this->resolveParameters();
    }

    public function testAddArgumentsWithoutNames(): void
    {
        $fn = static fn (string $value, SuperClass $class) => 'ok';
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())
            ->method('get')
        ;
        $this->setContainer($mockContainer);

        $this->arguments = $this->addArguments([
            'value',
            diAutowire(SuperClass::class), // 🚩 without array key as argument name
        ])->getBindArguments();

        $this->assertEquals('ok', \call_user_func_array($fn, $this->resolveParameters()));
    }

    public function testAddArgumentByIndex(): void
    {
        $fn = static fn (string $value, SuperClass $class) => 'ok';
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())
            ->method('get')
        ;
        $this->setContainer($mockContainer);

        $this->addArgument(0, 'value');
        $this->addArgument(1, diAutowire(SuperClass::class));

        $this->arguments = $this->getBindArguments();

        $this->assertEquals('ok', \call_user_func_array($fn, $this->resolveParameters()));
    }

    public function testAddArgumentsSuccess(): void
    {
        $fn = static fn (iterable $iterator, ?string $value = null) => \array_merge((array) $iterator, [$value]);
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();
        $this->setUseAttribute(false);

        $this->arguments = $this->addArguments([
            'iterator' => ['ok'],
        ])->getBindArguments();

        $this->assertEquals(['ok', null], \call_user_func_array($fn, $this->resolveParameters()));
    }

    public function testNoUserDefinedArgumentSuccess(): void
    {
        $fn = static fn (array $array = [], string $value = 'app') => $array + [$value];
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();
        $this->setUseAttribute(false);

        $this->arguments = $this->addArguments([])->getBindArguments();

        $this->assertEquals(['app'], \call_user_func_array($fn, $this->resolveParameters()));
    }
}
