<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class ContainerAccessByArrayNotationSymbolWithAttributeTest extends TestCase
{
    public function testGetParametersByDelimiterSymbol(): void
    {
        $c = (new DiContainerFactory())->make([
            'app' => [
                'users' => [
                    'ivan',
                    'piter',
                ],
                'city' => 'Washington',
                'sites' => [
                    'search' => 'https://google.com',
                ],
            ],
            'shared-data' => ['abc'],
            'config-table-name' => 'sure-table',
        ]);

        $class = $c->get(Fixtures\Attributes\NamesWithInject::class);

        $this->assertEquals(['ivan', 'piter'], $class->names);
        $this->assertEquals('Washington', $class->place);
        $this->assertEquals('https://google.com', $class->site);
        $this->assertInstanceOf(Fixtures\Attributes\SimpleDbInterface::class, $class->simpleDb);
        $this->assertEquals(['name' => 'ivan', 'table' => 'sure-table'], $class->simpleDb->select('ivan'));
        $this->assertEquals('user piter into table sure-table', $class->simpleDb->insert('piter'));
    }

    public function testGetParametersByDelimiterSymbolWrongKey(): void
    {
        $c = (new DiContainerFactory())->make([
            'app' => [
                'users_wrong_key' => [
                    'ivan',
                    'piter',
                ],
                'city' => 'Washington',
            ],
        ]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        $c->get(Fixtures\Attributes\NamesWithInject::class);
    }

    public function testInjectOtherClassByArrayNotated(): void
    {
        $def = [
            'app' => [
                'emails' => [
                    'admin' => 'admin@email.com',
                ],
                'logger' => Fixtures\Attributes\Logger::class,
                'logger_file' => '/var/logs/app.log',
            ],
        ];

        $container = (new DiContainerFactory())->make($def);

        $class = $container->get(Fixtures\Attributes\SendEmail::class);

        $this->assertEquals('/var/logs/app.log', $class->logger->file);
        $this->assertEquals('admin@email.com', $class->adminEmail);
        $this->assertEquals(['first' => '🥇', 'second' => '🥈'], $class->fromFactory);
    }

    public function testInjectOtherClassByArrayNotatedNotFound(): void
    {
        $def = [
            'app' => [
                'emails' => [
                    'admin' => 'admin@email.com',
                ],
            ],
        ];

        $container = (new DiContainerFactory())->make($def);

        $this->expectException(ContainerExceptionInterface::class);

        $container->get(Fixtures\Attributes\SendEmail::class);
    }
}
