<?php

declare(strict_types=1);

namespace Tests\SourceParameters;

use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Exception\ParameterCallCircularException;
use Kaspi\DiContainer\Exception\ParameterNotFoundException;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(ParameterCallCircularException::class)]
#[CoversClass(ParameterNotFoundException::class)]
#[CoversClass(NotFoundException::class)]
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

        self::assertEquals($expect, [...$p->parameters()]);
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
}
