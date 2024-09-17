<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Tests\Fixtures\Classes;
use Tests\Fixtures\Classes\Interfaces;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\Factory
 * @covers \Kaspi\DiContainer\Attributes\Inject::makeFromReflection
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerFactory::make
 */
class ContainerTest extends TestCase
{
    protected ?AutowiredInterface $autowire = null;

    protected function setUp(): void
    {
        $this->autowire = new Autowired();
    }

    protected function tearDown(): void
    {
        $this->autowire = null;
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
        $container->set('test', fn () => 10);

        $this->assertTrue($container->has('test'));
        $this->assertTrue($container->has(self::class));
    }

    public function testGetClosure(): void
    {
        $container = new DiContainer(autowire: $this->autowire);
        $i = 1;
        $container->set('test', fn () => 10 + $i);

        $this->assertEquals(11, $container->get('test'));
    }

    public function testAutowiredOff(): void
    {
        $container = (new DiContainer())
            ->set('test', fn () => \time())
        ;

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('autowire');

        $container->get('test');
    }

    public function testGetClosureWithGlobalParams(): void
    {
        $container = (new DiContainer(autowire: $this->autowire))
            ->set('myParams', 1)
            ->set('test', fn (int $myParams) => 10 + $myParams)
        ;

        $this->assertEquals(11, $container->get('test'));
    }

    public function testGetClosureWithParamsDefaultValue(): void
    {
        $container = new DiContainer(autowire: $this->autowire);
        $container->set('test', fn (int $myParams = 5) => 10 + $myParams);

        $this->assertEquals(15, $container->get('test'));
    }

    public function testGetClosureWithParamsDiContainer(): void
    {
        $container = new DiContainer([
            'param_five' => 5,
            'test' => static function (ContainerInterface $container) {
                return 10 + $container->get('param_five');
            },
        ], autowire: $this->autowire);

        $this->assertEquals(15, $container->get('test'));
    }

    public function testClassResolveWithCustomLinkSymbol(): void
    {
        $instances = [
            'all_records' => ['first', 'second'],
            Classes\Db::class => [
                'arguments' => [
                    'data' => '*all_records',
                ],
            ],
            Interfaces\CacheTypeInterface::class => Classes\FileCache::class,
        ];

        $container = new DiContainer(
            definitions: $instances,
            autowire: $this->autowire,
            linkContainerSymbol: '*'
        );

        $repository = $container->get(Classes\UserRepository::class);

        $this->assertEquals('first, second', $repository->all());
        $this->assertNull($repository->db->store);
    }

    public function testResolveByInterfaceWithNamedArgClassInstance(): void
    {
        $definitions = static function (): \Generator {
            yield Classes\UserRepository::class => [
                'arguments' => ['db' => '@database'],
            ];

            yield 'database' => static function (Interfaces\CacheTypeInterface $cache): Classes\Db {
                return new Classes\Db(['Lorem', 'Ipsum'], cache: $cache);
            };

            yield Interfaces\CacheTypeInterface::class => Classes\FileCache::class;
        };

        $container = new DiContainer($definitions(), $this->autowire);
        $repo = $container->get(Classes\UserRepository::class);

        $this->assertEquals('Lorem, Ipsum', $repo->all());
        $this->assertInstanceOf(Classes\FileCache::class, $repo->db->cache);
    }

    public function testResolveByInterfaceWithNamedArgCallableFunction(): void
    {
        $base = ['Lorem', 'Ipsum'];

        $definitions = static function () use ($base): \Generator {
            yield 'database' => static fn () => new Classes\Db($base);

            yield Classes\UserRepository::class => [
                'arguments' => ['db' => 'database'],
            ];
        };

        $container = new DiContainer($definitions(), $this->autowire);
        $repo = $container->get(Classes\UserRepository::class);

        $this->assertEquals('Lorem, Ipsum', $repo->all());
    }

    public function testSetWithParseParams(): void
    {
        $container = (new DiContainer(autowire: $this->autowire))
            ->set(
                Classes\Db::class,
                [
                    'arguments' => [
                        'data' => [],
                        'store' => '/var/log',
                    ],
                ],
            )
        ;

        $db = $container->get(Classes\Db::class);

        $this->assertEmpty($db->all());
        $this->assertEquals('/var/log', $db->store);
        $this->assertNull($db->cache);
    }

    public function testByInterfaceWithParams(): void
    {
        $instances = [
            Interfaces\SumInterface::class => Classes\Sum::class,
            Classes\Sum::class => [
                'arguments' => [
                    'init' => 50,
                ],
            ],
        ];

        $sum = (new DiContainer($instances, $this->autowire))
            ->get(Interfaces\SumInterface::class)
        ;

        $this->assertEquals(60, $sum->add(10));
    }

    public function testByInterfaceOnly(): void
    {
        $instances = [
            Interfaces\SumInterface::class => Classes\Sum::class,
        ];

        $sum = (new DiContainer($instances, $this->autowire))->get(Interfaces\SumInterface::class);

        $this->assertEquals('Init data 0', (string) $sum);
        $this->assertEquals(10, $sum->add(10));
    }

    public function testByInterfaceWithCallable(): void
    {
        $sum = (new DiContainer(autowire: $this->autowire))
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
        $container = (new DiContainer(autowire: $this->autowire))
            ->set('adminEmail', 'root@email.com')
            ->set('delay', 100)
        ;

        $sendEmail = $container->get(Classes\SendEmail::class);

        $this->assertEquals('root@email.com', $sendEmail->adminEmail);
        $this->assertTrue($sendEmail->confirm);

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
        $container = (new DiContainer(autowire: $this->autowire))
            ->set(Classes\NoConstructorAndInvokable::class)
        ;

        $result = $container->get(Classes\NoConstructorAndInvokable::class);

        $this->assertEquals('abc', $result());
    }

    public function testExistId(): void
    {
        $container = new DiContainer(['service' => 10]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('already registered');

        $container->set('service', 5);
    }

    public static function buildInObjectTypesData(): \Generator
    {
        yield 'stdClass' => [
            'obj' => new \stdClass(),
        ];

        yield 'anonymous class' => [
            'obj' => new class() {
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
        $container->set('name');

        $this->assertEquals('name', $container->get('name'));
    }

    public function testResolveWithoutConfig(): void
    {
        $instance = (new DiContainer(autowire: $this->autowire))
            ->get(Classes\CacheAll::class)
        ;

        $this->assertInstanceOf(Classes\CacheAll::class, $instance);
    }

    public function testContainerSetBuildInTypeAsArray(): void
    {
        $container = (new DiContainer(autowire: $this->autowire))
            ->set('data', ['one', 'two'])
        ;

        $this->assertEquals(['one', 'two'], $container->get('data'));
    }

    public function testClassWithSplClass(): void
    {
        $class = (new DiContainer(autowire: $this->autowire))
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
            'local_file' => 'logger_file',
        ];
        $definitions = \array_merge(
            $loggerConfig,
            [
                Classes\Logger::class => [
                    'arguments' => [
                        // get by container-id
                        'name' => 'logger_name_my_app',
                        // get by container link
                        'file' => 'local_file',
                    ],
                ],
            ]
        );

        $container = new DiContainer($definitions, $this->autowire);
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
            Classes\Names::class => [
                'arguments' => [
                    'place' => 'In the city',
                    'names' => ['Ivan', 'Piter'],
                ],
            ],
        ];

        $container = new DiContainer($def, $this->autowire);

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
        $container = new DiContainer(autowire: $this->autowire);
        $container->set(Classes\Logger::class, ['name' => 'log-app', 'file' => '/var/log/log.txt']);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Unresolvable dependency.*Logger::__construct/');

        $container->get(Classes\Logger::class);
    }

    public function testLinkSymbolByLinkSymbol(): void
    {
        $instances = [
            'shared_user' => ['first', 'second'],
            'all_records' => '@shared_user',
            Classes\Db::class => [
                'arguments' => [
                    'data' => '@all_records',
                ],
            ],
        ];

        $container = new DiContainer(definitions: $instances, autowire: $this->autowire);

        $this->assertEquals(['first', 'second'], $container->get(Classes\Db::class)->all());
    }

    public function testGetByLinkIdentifier(): void
    {
        $c = new DiContainer([
            'main' => 'Main value',
            'abc' => '@main',
            'x' => '@abc',
            'y' => '@x',
        ]);

        $this->assertEquals('Main value', $c->get('y'));
    }

    public function testDefinitionArgAsClosure(): void
    {
        $c = (new DiContainerFactory())->make([
            Classes\CacheAll::class => fn () => new Classes\CacheAll(new Classes\FileCache(), new Classes\RedisCache()),
        ]);

        $this->assertInstanceOf(Classes\CacheAll::class, $c->get(Classes\CacheAll::class));
    }

    public function testDefinitionAsFactory(): void
    {
        $c = (new DiContainerFactory())->make([
            Classes\Db::class => Classes\DbFactory::class,
        ]);

        $db = $c->get(Classes\Db::class);

        $this->assertEquals(['one', 'two'], $db->all());
        $this->assertInstanceOf(Interfaces\CacheTypeInterface::class, $db->cache);
        $this->assertEquals('::file::', $db->cache->driver());
    }

    public function testDefinitionAsFactoryWrong(): void
    {
        $c = (new DiContainerFactory())->make([
            Classes\Db::class => Classes\FileCache::class,
        ]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage("Definition argument 'Tests\\Fixtures\\Classes\\FileCache' must be a 'Kaspi\\DiContainer\\Interfaces\\FactoryInterface' interface");

        $c->get(Classes\Db::class);
    }
}
