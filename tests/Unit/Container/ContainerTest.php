<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\KeyGeneratorForNamedParameterInterface;
use Kaspi\DiContainer\KeyGeneratorForNamedParameter;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Tests\Fixtures\Classes;
use Tests\Fixtures\Classes\Interfaces;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Autowired::__construct
 * @covers \Kaspi\DiContainer\Autowired::filterInputArgs
 * @covers \Kaspi\DiContainer\Autowired::getKeyGeneratorForNamedParameter
 * @covers \Kaspi\DiContainer\Autowired::resolveInstance
 * @covers \Kaspi\DiContainer\Autowired::resolveParameters
 * @covers \Kaspi\DiContainer\DiContainer::__construct
 * @covers \Kaspi\DiContainer\DiContainer::get
 * @covers \Kaspi\DiContainer\DiContainer::has
 * @covers \Kaspi\DiContainer\DiContainer::parseConstructorArguments
 * @covers \Kaspi\DiContainer\DiContainer::resolve
 * @covers \Kaspi\DiContainer\DiContainer::set
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter::__construct
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter::delimiter
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter::id
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter::idConstructor
 */
class ContainerTest extends TestCase
{
    protected ?KeyGeneratorForNamedParameterInterface $keyGeneratorForNameParameter = null;
    protected ?AutowiredInterface $autowire = null;

    protected function setUp(): void
    {
        $this->keyGeneratorForNameParameter = new KeyGeneratorForNamedParameter();
        $this->autowire = new Autowired($this->keyGeneratorForNameParameter);
    }

    protected function tearDown(): void
    {
        $this->keyGeneratorForNameParameter = null;
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
            ->set('test', fn () => time())
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

    public function testClassResolve(): void
    {
        // Delimiter for params name
        $paramsDelimiter = '*';

        $instances = [
            'all_records' => ['first', 'second'],
            Classes\Db::class => [
                DiContainer::ARGUMENTS => [
                    'data' => '*all_records',
                ],
            ],
            Interfaces\CacheTypeInterface::class => Classes\FileCache::class,
        ];

        $keyGen = new KeyGeneratorForNamedParameter($paramsDelimiter);
        $container = new DiContainer(
            definitions: $instances,
            autowire: new Autowired($keyGen),
            keyGenerator: $keyGen
        );

        $repository = $container->get(Classes\UserRepository::class);

        $this->assertEquals('first, second', $repository->all());
        $this->assertNull($repository->db->store);
        $this->assertTrue($container->has(Classes\Db::class.'*__construct*data'));
    }

    public function testResolveByInterfaceWithNamedArgClassInstance(): void
    {
        $definitions = static function (): \Generator {
            yield Interfaces\CacheTypeInterface::class => Classes\FileCache::class;

            yield 'database' => static function (Interfaces\CacheTypeInterface $cache): Classes\Db {
                return new Classes\Db(['Lorem', 'Ipsum'], cache: $cache);
            };

            yield Classes\UserRepository::class => [
                DiContainer::ARGUMENTS => ['db' => '@database'],
            ];
        };

        $keyGen = new KeyGeneratorForNamedParameter();
        $container = new DiContainer(
            $definitions(),
            new Autowired($keyGen),
            $keyGen
        );
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
                DiContainer::ARGUMENTS => ['db' => '@database'],
            ];
        };

        $keyGen = new KeyGeneratorForNamedParameter();
        $container = new DiContainer(
            $definitions(),
            new Autowired($keyGen),
            $keyGen
        );
        $repo = $container->get(Classes\UserRepository::class);

        $this->assertEquals('Lorem, Ipsum', $repo->all());
    }

    public function testSetWithParseParams(): void
    {
        $container = (new DiContainer(autowire: $this->autowire, keyGenerator: $this->keyGeneratorForNameParameter))
            ->set(
                Classes\Db::class,
                [
                    DiContainer::ARGUMENTS => [
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
                DiContainer::ARGUMENTS => [
                    'init' => 50,
                ],
            ],
        ];

        $sum = (new DiContainer($instances, $this->autowire, $this->keyGeneratorForNameParameter))
            ->get(Interfaces\SumInterface::class)
        ;

        $this->assertEquals(60, $sum->add(10));
    }

    public function testByInterfaceOnly(): void
    {
        $instances = [
            Interfaces\SumInterface::class => Classes\Sum::class,
        ];

        $sum = (new DiContainer($instances, $this->autowire, $this->keyGeneratorForNameParameter))
            ->get(Interfaces\SumInterface::class)
        ;

        $this->assertEquals('Init data 0', (string) $sum);
        $this->assertEquals(10, $sum->add(10));
    }

    public function testByInterfaceWithCallable(): void
    {
        $sum = (new DiContainer(autowire: $this->autowire, keyGenerator: $this->keyGeneratorForNameParameter))
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

    public function testResolveConstructorParametersNaming(): void
    {
        $keyGen = new KeyGeneratorForNamedParameter('.');
        $autowire = new Autowired($keyGen);
        $container = (new DiContainer(autowire: $autowire, keyGenerator: $keyGen))
            ->set(
                id: Classes\Sum::class,
                arguments: ['init' => 200]
            )
        ;
        $container->get(Classes\Sum::class);

        $init = $container->get(Classes\Sum::class.'.__construct.init');

        $this->assertEquals(200, $init);
    }

    public function testDelimiterForNotationParamAndClass(): void
    {
        $keyGen = new KeyGeneratorForNamedParameter('!');
        $autowire = new Autowired($keyGen);
        $container = (new DiContainer(autowire: $autowire, keyGenerator: $keyGen))
            ->set(
                Classes\Sum::class,
                [
                    DiContainer::ARGUMENTS => [
                        'init' => 99,
                    ],
                ]
            )
        ;
        $container->get(Classes\Sum::class);

        $this->assertTrue($container->has(Classes\Sum::class.'!__construct!init'));
        $this->assertEquals(99, $container->get(Classes\Sum::class.'!__construct!init'));
    }

    public function testResolveConstructorStringParameter(): void
    {
        $container = (new DiContainer(autowire: $this->autowire, keyGenerator: $this->keyGeneratorForNameParameter))
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
        $container = (new DiContainer(autowire: $this->autowire, keyGenerator: $this->keyGeneratorForNameParameter))
            ->set(Classes\NoConstructorAndInvokable::class)
        ;

        $result = $container->get(Classes\NoConstructorAndInvokable::class);

        $this->assertEquals('abc', $result());
    }

    public function testExistId(): void
    {
        $container = new DiContainer(['service' => 10]);
        $this->expectException(ContainerExceptionInterface::class);

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
                    return time();
                }
            },
        ];

        yield 'resource' => [
            'obj' => tmpfile(),
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
        $instance = (new DiContainer(autowire: $this->autowire, keyGenerator: $this->keyGeneratorForNameParameter))
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
        $class = (new DiContainer(autowire: $this->autowire, keyGenerator: $this->keyGeneratorForNameParameter))
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
            'loggerFile' => '/path/to/your.log',
            'loggerName' => 'app-logger',
        ];
        $definitions = array_merge(
            $loggerConfig,
            [
                Classes\Logger::class => [
                    DiContainer::ARGUMENTS => [
                        'name' => '@loggerName',
                        'file' => '@loggerFile',
                    ],
                ],
            ]
        );

        $container = new DiContainer($definitions, $this->autowire, $this->keyGeneratorForNameParameter);
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
        ];

        $container = new DiContainer($def, $this->autowire);

        $this->assertEquals([
            'arguments' => [
                'name' => 'John',
            ],
        ], $container->get('Welcome'));
    }
}
