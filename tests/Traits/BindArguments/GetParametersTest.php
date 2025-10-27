<?php

declare(strict_types=1);

namespace Tests\Traits\BindArguments;

use ArrayIterator;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use stdClass;

use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getParameterType
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 *
 * @internal
 */
class GetParametersTest extends TestCase
{
    use BindArgumentsTrait;
    use DiContainerTrait;

    public function testGetWithoutParameters(): void
    {
        $fn = static fn () => '';

        $this->bindArguments('one', 'two', diGet('services.logger_file'));
        $params = (new ReflectionFunction($fn))->getParameters();

        self::assertEquals(
            ['one', 'two', diGet('services.logger_file')],
            $this->getParameters($params, false)
        );
    }

    public function testResolveByParameterTypeByParameterNameByDefaultValue(): void
    {
        $fn = static fn (ArrayIterator $iterator, $serviceLook, $dto = new stdClass()): array => [$iterator, $serviceLook, $dto];

        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = self::createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                ['ArrayIterator', true],
                ['serviceLook', true],
                ['dto', false],
            ])
        ;

        $this->setContainer($containerMock);

        self::assertEquals(
            [diGet('ArrayIterator'), diGet('serviceLook'), new stdClass()],
            $this->getParameters($params, false)
        );
    }
}
