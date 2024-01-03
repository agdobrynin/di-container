<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes\InjectFailType;
use Tests\Fixtures\Attributes\InjectSimpleArgument;
use Tests\Fixtures\Attributes\Lorem;
use Tests\Fixtures\Attributes\SimpleDb;
use Tests\Fixtures\Attributes\SimpleDbInterface;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Inject::makeFromReflection
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerFactory
 */
class ContainerWithAttributeConfigTest extends TestCase
{
    public function testGetServiceByInterface(): void
    {
        $c = DiContainerFactory::make([
            'shared-data' => ['php', 'js'],
            'config-table-name' => 'log',
        ]);

        /** @var Lorem $l */
        $l = $c->get(Lorem::class);

        $this->assertInstanceOf(Lorem::class, $l);
        $this->assertInstanceOf(SimpleDbInterface::class, $l->simpleDb);
        $this->assertEquals('insert Ivan into table log', $l->simpleDb->insert('Ivan'));
        $this->assertEquals(['name' => 'Piter', 'table' => 'log'], $l->simpleDb->select('Piter'));

        $this->assertEquals(['php', 'js'], $l->simpleDb->data->getArrayCopy());
    }

    public function testInjectSimpleDataByLink(): void
    {
        $c = DiContainerFactory::make([
            'data' => ['one', 'second'],
            'shared-data' => '@data',
            'config-table-name' => 'log',
        ]);

        $class = $c->get(SimpleDb::class);

        $this->assertInstanceOf(\ArrayIterator::class, $class->data);
        $this->assertEquals(['one', 'second'], $class->data->getArrayCopy());
    }

    public function testInjectFailType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('must be of type SplQueue');

        DiContainerFactory::make()
            ->get(InjectFailType::class)
        ;
    }

    public function testInjectWithSimpleArguments(): void
    {
        $c = DiContainerFactory::make();

        $class = $c->get(InjectSimpleArgument::class);

        $this->assertEquals(['first' => '🥇', 'second' => '🥈'], $class->arrayIterator()->getArrayCopy());
        $this->assertInstanceOf(\ArrayAccess::class, $class->arrayIterator());
    }
}
