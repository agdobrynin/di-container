<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\Factory
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
        $c = (new DiContainerFactory())->make([
            'shared-data' => ['php', 'js'],
            'config-table-name' => 'log',
        ]);

        $l = $c->get(Attributes\Lorem::class);

        $this->assertInstanceOf(Attributes\Lorem::class, $l);
        $this->assertInstanceOf(Attributes\SimpleDbInterface::class, $l->simpleDb);
        $this->assertEquals('user Ivan into table log', $l->simpleDb->insert('Ivan'));
        $this->assertEquals(['name' => 'Piter', 'table' => 'log'], $l->simpleDb->select('Piter'));

        $this->assertEquals(['php', 'js'], $l->simpleDb->data->getArrayCopy());
    }

    public function testInjectSimpleDataByLink(): void
    {
        $c = (new DiContainerFactory())->make([
            'data' => ['one', 'second'],
            'shared-data' => '@data',
            'config-table-name' => 'log',
        ]);

        $class = $c->get(Attributes\SimpleDb::class);

        $this->assertInstanceOf(\ArrayIterator::class, $class->data);
        $this->assertEquals(['one', 'second'], $class->data->getArrayCopy());
    }

    public function testInjectFailType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('must be of type SplQueue');

        (new DiContainerFactory())->make()
            ->get(Attributes\InjectFailType::class)
        ;
    }

    public function testInjectSplClass(): void
    {
        $class = (new DiContainerFactory())->make()
            ->get(Attributes\InjectSplClass::class)
        ;

        $this->assertInstanceOf(\SplQueue::class, $class->queue);
    }

    public function testInjectWithSimpleArguments(): void
    {
        $c = (new DiContainerFactory())->make();

        $class = $c->get(Attributes\InjectSimpleArgument::class);

        $this->assertEquals(['first' => '🥇', 'second' => '🥈'], $class->arrayIterator()->getArrayCopy());
        $this->assertInstanceOf(\ArrayAccess::class, $class->arrayIterator());
    }

    public static function containerDefinitions(): \Generator
    {
        yield 'with default value from definitions' => [
            'definitions' => [
                'shared-data' => ['abc', 'efj'],
                'config-table-name' => 'cococo',
                'app' => [
                    'logger_file' => '/var/log/app.log',
                    'defaultName' => 'Piter',
                ],
            ],
            'expect' => 'I log to [/var/log/app.log] with data [user Piter into table cococo]',
        ];

        yield 'with value as argument' => [
            'definitions' => [
                'shared-data' => ['abc', 'efj'],
                'config-table-name' => 'cococo',
                'app' => [
                    'logger_file' => '/var/log/app.log',
                    'defaultName' => 'Piter',
                ],
            ],
            'expect' => 'I log to [/var/log/app.log] with data [user Vasiliy into table cococo]',
            'methodArgs' => [
                'userName' => 'Vasiliy',
            ],
        ];
    }

    /**
     * @dataProvider containerDefinitions
     */
    public function testMethodResolve(array $definitions, string $expect, array $methodArgs = []): void
    {
        $a = new Autowired();
        $c = (new DiContainer(definitions: $definitions, autowire: $a))
            ->set(AutowiredInterface::class, Autowired::class)
        ;

        $res = $c->get(AutowiredInterface::class)
            ->callMethod($c, Attributes\Lorem::class, 'doIt', [], $methodArgs)
        ;

        $this->assertEquals($expect, $res);
    }

    public function testMethodNotFound(): void
    {
        $a = new Autowired();
        $c = (new DiContainer(definitions: [
            'shared-data' => ['abc', 'efj'],
            'config-table-name' => 'cococo',
        ], autowire: $a))
            ->set(AutowiredInterface::class, Autowired::class)
        ;

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('notExistMethod() does not exist');

        $c->get(AutowiredInterface::class)
            ->callMethod($c, Attributes\Lorem::class, 'notExistMethod')
        ;
    }
}
