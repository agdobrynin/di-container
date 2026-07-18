<?php

declare(strict_types=1);

namespace Tests\SourceParameters;

use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Exception\ParameterCallCircularException;
use Kaspi\DiContainer\Exception\ParameterException;
use Kaspi\DiContainer\Exception\ParameterNotFoundException;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\Parameters\SourceParameterItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

use function current;
use function next;

/**
 * @internal
 */
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(ParameterCallCircularException::class)]
#[CoversClass(ParameterNotFoundException::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(SourceParameterItem::class)]
class ImmediateSourceParametersTest extends TestCase
{
    #[TestWith([
        'foo',
        [
            'foo' => '{bar}',
            'bar' => '{baz}',
        ],
        [
            'baz' => '{foo}',
        ],
    ])]
    #[TestWith([
        'foo',
        [
            'foo' => [
                '{foo}' => true,
            ],
        ],
    ])]
    #[TestWith([
        'foo',
        [
            'foo' => [0 => '{foo}'],
        ],
    ])]
    public function testCircularParameters(string $name, array $params, array $addParams = []): void
    {
        $this->expectException(ParameterExceptionInterface::class);
        $this->expectExceptionMessage('Trying call cyclical parameter name');

        $p = new ImmediateSourceParameters($params);

        $p->add($addParams);

        $p->get($name);
    }

    public function testOneValue(): void
    {
        $p = new ImmediateSourceParameters([
            'foo' => 'one.{bar}.two.{bar}',
            'bar' => '{baz}',
            'baz' => 'qux',
        ]);

        self::assertSame('one.qux.two.qux', $p->get('foo'));
    }

    #[DataProviderExternal(ParameterDataset::class, 'notFound')]
    public function testParameterNotFound(iterable $params, string $getParamName, string $regExpExpectExceptionMessage): void
    {
        $this->expectException(ParameterNotFoundExceptionInterface::class);
        $this->expectExceptionMessageMatches($regExpExpectExceptionMessage);

        $p = new ImmediateSourceParameters($params);
        $p->get($getParamName);
    }

    #[DataProviderExternal(ParameterDataset::class, 'successAndCaching')]
    public function testParametersSuccess(iterable $params, array $expect): void
    {
        $p = new ImmediateSourceParameters();
        $p->add($params);

        $paramsIterator = $p->parameters();

        while ($paramsIterator->valid()) {
            self::assertEquals(current($expect), $paramsIterator->current());

            next($expect);
            $paramsIterator->next();
        }
    }

    #[TestWith([['foo' => new stdClass()]])]
    #[TestWith([['foo' => ['bar' => ['baz' => new stdClass()]]]])]
    #[TestWith([['foo' => ['bar' => ['baz' => '{qux}']], 'qux' => new stdClass()]])]
    #[TestWith([['foo' => ['{bar}' => true], 'bar' => null], 'Array key must be resolve as integer or string type'])]
    public function testUnsupportedParameterValue(array $params, string $expectMessage = 'unsupported value type'): void
    {
        $this->expectException(ParameterExceptionInterface::class);
        $this->expectExceptionMessage($expectMessage);

        $p = new ImmediateSourceParameters();
        $p->add($params);
        $p->get('foo');
    }

    #[TestWith([['foo' => 'test:{bar}', 'bar' => false]])]
    #[TestWith([['foo' => 'test:{bar}', 'bar' => 'test:{baz}', 'baz' => ParamEnum::SECOND]])]
    #[TestWith([['foo' => 'test:{bar}', 'bar' => '{baz}', 'baz' => new stdClass()]])]
    public function testConcatenateNoneStringOrNoneNumeric(array $params): void
    {
        $this->expectException(ParameterExceptionInterface::class);

        $p = new ImmediateSourceParameters();
        $p->add($params);
        $p->get('foo');
    }

    public function testCannotReplaceParameter(): void
    {
        $this->expectException(ParameterExceptionInterface::class);

        $p = new ImmediateSourceParameters(['foo' => 'bar']);
        $p->set('foo', 'baz');
    }

    public function testGetParameterWithException(): void
    {
        $this->expectException(ParameterExceptionInterface::class);
        $this->expectExceptionMessage('Something went wrong!');

        $p = new ImmediateSourceParameters([
            'foo' => '{bar}',
            'bar' => ['Lorem ipsum', '{baz}'],
            'baz' => new ParameterException('Something went wrong!'),
        ]);

        $p->get('foo');
    }

    public function testFallbackEnabledForGetParameters(): void
    {
        $p = new ImmediateSourceParameters([
            'foo' => 'Lorem ipsum',
            'baz' => '{none-exist-param-name}',
            'bar' => new stdClass(),
        ]);

        $fallback = static fn (string $name, Throwable $exception): mixed => $exception;

        $params = $p->parameters($fallback);

        self::assertEquals('foo', $params->key());
        self::assertEquals('Lorem ipsum', $params->current());

        $params->next();

        self::assertEquals('baz', $params->key());
        self::assertInstanceOf(ParameterNotFoundExceptionInterface::class, $params->current());
        self::assertStringContainsString('Parameter name "none-exist-param-name" not found.', $params->current()->getMessage());

        $params->next();

        self::assertEquals('bar', $params->key());
        self::assertInstanceOf(ParameterExceptionInterface::class, $params->current());
        self::assertStringContainsString('unsupported value type: "stdClass".', $params->current()->getMessage());
    }

    public function testFallbackDisabledForGetParameters(): void
    {
        $p = new ImmediateSourceParameters([
            'foo' => 'Lorem ipsum',
            'baz' => '{none-exist-param-name}',
        ]);

        $params = $p->parameters();

        self::assertEquals('foo', $params->key());
        self::assertEquals('Lorem ipsum', $params->current());

        $this->expectException(ParameterNotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Parameter name "none-exist-param-name" not found.');

        $params->next();
    }
}
