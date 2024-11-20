<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Fixtures\Attributes\FactoryClassWithDiFactoryArgument;
use Tests\Fixtures\Classes;
use Tests\Fixtures\Classes\Interfaces;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diReference;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory::make
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 */
class ContainerTest extends TestCase
{
    protected ?DiContainerConfig $diContainerConfig = null;

    protected function setUp(): void
    {
        $this->diContainerConfig = new DiContainerConfig();
    }

    protected function tearDown(): void
    {
        $this->diContainerConfig = null;
    }

    public function testImplementContainerPsr(): void
    {
        $container = new DiContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testHasMethodIsFalse(): void
    {
        $container = new DiContainer();

        $this->assertFalse($container->has('test'));
    }

    public function testHasMethodIsTrue(): void
    {
        $container = new DiContainer();
        $container->set('test', static fn () => 10);

        $this->assertTrue($container->has('test'));
        $this->assertFalse($container->has(self::class));
    }

    public function testGetClosure(): void
    {
        $container = new DiContainer(config: $this->diContainerConfig);
        $i = 1;
        $container->set('test', static fn () => 10 + $i);

        $this->assertEquals(11, $container->get('test'));
    }

    public function testGetClosureWithGlobalParams(): void
    {
        $fn = static fn (int $myParams) => 10 + $myParams;

        $container = (new DiContainer(config: $this->diContainerConfig))
            ->set('myParams', 1)
            ->set('test', $fn)
            ->set('test2', diAutowire(FactoryClassWithDiFactoryArgument::class))
        ;

        $this->assertEquals(11, $container->get('test'));
        $this->assertCount(0, $container->get('test2'));
    }

    public function testGetClosureWithParamsDefaultValue(): void
    {
        $container = (new DiContainer(config: $this->diContainerConfig));
        $container->set('test', static fn (int $myParams = 5) => 10 + $myParams);

        $this->assertEquals(15, $container->get('test'));
    }

    public function testGetClosureWithParamsDiContainer(): void
    {
        $container = new DiContainer([
            'param_five' => 5,
            'test' => static function (ContainerInterface $container) {
                return 10 + $container->get('param_five');
            },
        ], config: $this->diContainerConfig);

        $this->assertEquals(15, $container->get('test'));
    }

    public function testResolveByInterfaceWithNamedArgClassInstance(): void
    {
        $definitions = static function (): \Generator {
            yield diAutowire(Classes\UserRepository::class)
                ->addArgument('db', diReference('database'))
            ;

            yield 'database' => static function (Interfaces\CacheTypeInterface $cache): Classes\Db {
                return new Classes\Db(['Lorem', 'Ipsum'], cache: $cache);
            };

            yield Interfaces\CacheTypeInterface::class => diAutowire(Classes\FileCache::class);
        };

        $container = new DiContainer($definitions(), $this->diContainerConfig);
        $repo = $container->get(Classes\UserRepository::class);

        $this->assertEquals('Lorem, Ipsum', $repo->all());
        $this->assertInstanceOf(Classes\FileCache::class, $repo->db->cache);
    }

    public function testResolveByInterfaceWithNamedArgCallableFunction(): void
    {
        $base = ['Lorem', 'Ipsum'];

        $definitions = static function () use ($base): \Generator {
            yield 'database' => static fn () => new Classes\Db($base);

            yield diAutowire(Classes\UserRepository::class, ['db' => diReference('database')]);
        };

        $container = new DiContainer($definitions(), $this->diContainerConfig);
        $repo = $container->get(Classes\UserRepository::class);

        $this->assertEquals('Lorem, Ipsum', $repo->all());
    }

    public function testByInterfaceWithParams(): void
    {
        $definitions = [
            Interfaces\SumInterface::class => diAutowire(Classes\Sum::class, ['init' => 50]),
            diAutowire(Classes\Sum::class, ['init' => 10]),
        ];

        $c = new DiContainer($definitions, $this->diContainerConfig);

        $this->assertEquals(60, $c->get(Interfaces\SumInterface::class)->add(10));
        $this->assertEquals(20, $c->get(Classes\Sum::class)->add(10));
    }

    public function testByInterfaceOnly(): void
    {
        $instances = [
            Interfaces\SumInterface::class => diAutowire(Classes\Sum::class),
        ];

        $sum = (new DiContainer($instances, $this->diContainerConfig))->get(Interfaces\SumInterface::class);

        $this->assertEquals('Init data 0', (string) $sum);
        $this->assertEquals(10, $sum->add(10));
    }

    public function testByInterfaceWithCallable(): void
    {
        $sum = (new DiContainer(config: $this->diContainerConfig))
            ->set(
                Interfaces\SumInterface::class,
                static fn () => new Classes\Sum(100)
            )
            ->get(Interfaces\SumInterface::class)
        ;

        $this->assertInstanceOf(Classes\Sum::class, $sum);
        $this->assertEquals('Init data 100', (string) $sum);
        $this->assertEquals(110, $sum->add(10));
    }

    public function testResolveConstructorStringParameter(): void
    {
        $container = (new DiContainer(config: $this->diContainerConfig))
            ->set('adminEmail', 'root@email.com')
            ->set('delay', 100)
        ;

        $sendEmail = $container->get(Classes\SendEmail::class);

        $this->assertEquals('root@email.com', $sendEmail->adminEmail);
        $this->assertTrue($sendEmail->confirm);
        $this->assertNull($sendEmail->logger);

        $reportEmail = $container->get(Classes\ReportEmail::class);

        $this->assertEquals('root@email.com', $reportEmail->adminEmail);
        $this->assertEquals(100, $reportEmail->delay);
        $this->assertEquals('admin<root@email.com>', $reportEmail->emailWith());
    }

    public function testException(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $container = new DiContainer();
        $container->get('test');
    }

    public function testNoConstructor(): void
    {
        $container = (new DiContainer(config: $this->diContainerConfig))
            ->set('invokable', diCallable(Classes\NoConstructorAndInvokable::class))
        ;

        $result = $container->get('invokable');

        $this->assertEquals('abc', $result);
    }

    public function testExistId(): void
    {
        $container = new DiContainer(['service' => 10]);

        $this->expectException(ContainerAlreadyRegisteredExceptionInterface::class);
        $this->expectExceptionMessage('already registered');

        $container->set('service', 5);
    }

    public static function buildInObjectTypesData(): \Generator
    {
        yield 'stdClass' => [
            'obj' => new \stdClass(),
        ];

        yield 'anonymous class' => [
            'obj' => new class {
                public function time()
                {
                    return \time();
                }
            },
        ];

        yield 'resource' => [
            'obj' => \tmpfile(),
        ];
    }

    /**
     * @dataProvider buildInObjectTypesData
     */
    public function testBuildInObjectTypes(mixed $obj): void
    {
        $container = (new DiContainer())->set('obj', $obj);

        $this->assertSame($obj, $container->get('obj'));
    }

    public function testSetAsAbstract(): void
    {
        $container = new DiContainer();
        $container->set('name', '');

        $this->assertEquals('', $container->get('name'));
    }

    public function testResolveWithoutConfig(): void
    {
        $instance = (new DiContainer(config: $this->diContainerConfig))
            ->get(Classes\CacheAll::class)
        ;

        $this->assertInstanceOf(Classes\CacheAll::class, $instance);
    }

    public function testContainerSetBuildInTypeAsArray(): void
    {
        $container = (new DiContainer(config: $this->diContainerConfig))
            ->set('data', ['one', 'two'])
        ;

        $this->assertEquals(['one', 'two'], $container->get('data'));
    }

    public function testClassWithSplClass(): void
    {
        $class = (new DiContainer(config: $this->diContainerConfig))
            ->get(Classes\ClassWithSplClass::class)
        ;

        $this->assertInstanceOf(
            Classes\ClassWithSplClass::class,
            $class
        );
        $this->assertInstanceOf(\SplQueue::class, $class->queue);
    }

    public function testMultipleGlobalArguments(): void
    {
        $loggerConfig = [
            'logger_file' => '/path/to/your.log',
            'logger_name_my_app' => 'app-logger',
            'local_file' => diReference('logger_file'),
        ];

        $definitions = \array_merge(
            $loggerConfig,
            [
                diAutowire(Classes\Logger::class)
                    ->addArgument('name', diReference('logger_name_my_app'))
                    ->addArgument('file', diReference('local_file')),
            ]
        );

        $container = new DiContainer($definitions, config: $this->diContainerConfig);
        $logger = $container->get(Classes\Logger::class);

        $this->assertEquals('app-logger', $logger->name);
        $this->assertEquals('/path/to/your.log', $logger->file);
    }

    public function testParseConstructorArgumentWithoutAutowire(): void
    {
        $def = [
            'Welcome' => [
                'arguments' => [
                    'name' => 'John',
                ],
            ],
        ];

        $container = new DiContainer($def);

        $this->assertEquals([
            'arguments' => [
                'name' => 'John',
            ],
        ], $container->get('Welcome'));
    }

    public function testParseConstructorArgumentWithAutowire(): void
    {
        $def = [
            'Welcome' => [
                'arguments' => [
                    'name' => 'John',
                ],
            ],
            diAutowire(
                Classes\Names::class,
                [
                    'place' => 'In the city',
                    'names' => ['Ivan', 'Piter'],
                ],
            ),
        ];

        $container = new DiContainer(definitions: $def, config: $this->diContainerConfig);

        $this->assertEquals([
            'arguments' => [
                'name' => 'John',
            ],
        ], $container->get('Welcome'));

        $this->assertEquals(['Ivan', 'Piter'], $container->get(Classes\Names::class)->names);
        $this->assertEquals('In the city', $container->get(Classes\Names::class)->place);
    }

    public function testParseConstructorArguments(): void
    {
        $container = new DiContainer(config: $this->diContainerConfig);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Unresolvable dependency.*Logger::__construct/');

        $container->get(Classes\Logger::class);
    }

    public function testLinkSymbolByLinkSymbol(): void
    {
        $instances = [
            'shared_user' => ['first', 'second'],
            'all_records' => diReference('shared_user'),
            diAutowire(Classes\Db::class, ['data' => diReference('all_records')]),
        ];

        $container = new DiContainer(definitions: $instances, config: $this->diContainerConfig);

        $this->assertEquals(['first', 'second'], $container->get(Classes\Db::class)->all());
    }

    public function testGetByLinkIdentifier(): void
    {
        $c = new DiContainer(
            definitions: [
                'main' => 'Main value',
                'abc' => diReference('main'),
                'x' => diReference('abc'),
                'y' => diReference('x'),
            ],
            config: $this->diContainerConfig
        );

        $this->assertEquals('Main value', $c->get('y'));
    }

    public function testDefinitionArgAsClosure(): void
    {
        $c = (new DiContainerFactory())->make([
            Classes\CacheAll::class => static fn () => new Classes\CacheAll(new Classes\FileCache(), new Classes\RedisCache()),
        ]);

        $this->assertInstanceOf(Classes\CacheAll::class, $c->get(Classes\CacheAll::class));
    }

    public function testDefinitionAsFactory(): void
    {
        $c = (new DiContainerFactory())->make([
            Classes\Db::class => diAutowire(Classes\DbDiFactory::class),
        ]);

        $db = $c->get(Classes\Db::class);

        $this->assertEquals(['one', 'two'], $db->all());
        $this->assertInstanceOf(Interfaces\CacheTypeInterface::class, $db->cache);
        $this->assertEquals('::file::', $db->cache->driver());
    }

    public function testAbstractClassInNotInstantiable(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable');

        (new DiContainerFactory())->make()->get(Classes\AbstractClass::class);
    }

    public function testPrivateConstructor(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable');

        (new DiContainerFactory())->make([
            diAutowire(Classes\PrivateConstructorClass::class),
        ])->get(Classes\PrivateConstructorClass::class);
    }

    public function testResolveInterfaceFail(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Class "ok" does not exist');

        (new DiContainerFactory())->make([
            Interfaces\SumInterface::class => diAutowire('ok'),
        ])->get(Interfaces\SumInterface::class);
    }

    public function testGetContainerInterfaceWithoutDefinition(): void
    {
        $c = new DiContainer(config: $this->diContainerConfig);

        $this->assertInstanceOf(ContainerInterface::class, $c->get(ContainerInterface::class));
        $this->assertInstanceOf(ContainerInterface::class, $c->get(DiContainer::class));
        $this->assertInstanceOf(DiContainer::class, $c->get(DiContainer::class));
    }

    public function testGetContainerInterfaceWithDefinitionInDiContainerFactory(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->assertInstanceOf(ContainerInterface::class, $container->get(ContainerInterface::class));
        $this->assertInstanceOf(DiContainerInterface::class, $container->get(ContainerInterface::class));
    }

    public function testResolveInterfaceWithoutAttribute(): void
    {
        $container = new DiContainer([], new DiContainerConfig(useAttribute: false));

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Definition not found for identifier Tests\Fixtures\Classes\Interfaces\SumInterface');

        $container->get(Interfaces\SumInterface::class);
    }

    public function testHasValueNull(): void
    {
        $container = (new DiContainerFactory())->make(['keyNull' => null]);
        $container->set('null', null);

        $this->assertTrue($container->has('keyNull'));
        $this->assertNull($container->get('keyNull'));

        $this->assertTrue($container->has('null'));
        $this->assertNull($container->get('null'));
    }
}
