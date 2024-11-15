<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Attributes;
use Tests\Fixtures\Attributes\InjectStupidSimpleType;
use Tests\Fixtures\Attributes\SendEmail;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 */
class ContainerWithAttributeConfigTest extends TestCase
{
    public function testGetServiceByInterface(): void
    {
        $c = (new DiContainerFactory())->make([
            'shared-data' => ['php', 'js'],
            'config-table-name' => 'logs',
        ]);

        $l = $c->get(Attributes\Lorem::class);

        $this->assertInstanceOf(Attributes\Lorem::class, $l);
        $this->assertInstanceOf(Attributes\SimpleDbInterface::class, $l->simpleDb);
        $this->assertEquals('user Ivan into table logs', $l->simpleDb->insert('Ivan'));
        $this->assertEquals(['name' => 'Piter', 'table' => 'logs'], $l->simpleDb->select('Piter'));

        $this->assertEquals(['php', 'js'], $l->simpleDb->data->getArrayCopy());
    }

    public function testInjectSimpleDataByLink(): void
    {
        $c = (new DiContainerFactory())->make([
            'data' => ['one', 'second'],
            'shared-data' => '@data',
            'config-table-name' => 'logs',
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

    public function testInjectSimpleArgumentAndFactory(): void
    {
        $c = (new DiContainerFactory())->make(['emails.admin' => 'ivan@mail.com']);

        $sendMail = $c->get(SendEmail::class);

        $this->assertEquals('ivan@mail.com', $sendMail->adminEmail);
        $this->assertEquals(['first' => '🥇', 'second' => '🥈'], $sendMail->fromFactory);
    }

    public function testInjectSimpleTypeDirect(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency [rules]');

        $c->get(InjectStupidSimpleType::class);
    }
}
