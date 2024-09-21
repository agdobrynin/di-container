<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Fixtures\Classes;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class ContainerAccessByArrayNotationSymbolTest extends TestCase
{
    public function testAccessForSimpleArray(): void
    {
        $def = [
            'app' => [
                'users' => ['Ivan', 'Piter'],
                'city' => 'Washington',
            ],
            'emails' => [
                'admin' => 'admin@mail.com',
            ],
            'google' => 'https://www.google.com',
            'search_site' => 'https://www.google.com',
            'report' => [
                'reportEmail' => static function (ContainerInterface $container) {
                    return new Classes\ReportEmail($container->get('@emails.admin'), 0);
                },
            ],

            // ... more other definitions

            Classes\Names::class => [
                'arguments' => [
                    'names' => '@app.users',
                    'place' => '@app.city',
                    'site' => 'search_site',
                    'reportEmail' => '@report.reportEmail',
                ],
            ],
        ];

        $container = (new DiContainerFactory())->make($def);

        $class = $container->get(Classes\Names::class);

        $this->assertEquals(['Ivan', 'Piter'], $class->names);
        $this->assertEquals('Washington', $class->place);
        $this->assertEquals('https://www.google.com', $class->site);
        $this->assertEquals('admin<admin@mail.com>', $class->reportEmail->emailWith());
    }

    public function testUnresolvedArrayAccessParam(): void
    {
        $def = [
            'app' => [
                'wrong_notation_users' => ['Ivan', 'Piter'],
                'city' => 'Washington',
            ],
            // ... more other definitions

            Classes\Names::class => [
                'arguments' => [
                    'names' => '@app.users',
                    'place' => '@app.city',
                ],
            ],
        ];

        $container = (new DiContainerFactory())->make($def);

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency: array notation key [@app.users]');

        $container->get(Classes\Names::class);
    }

    public function testDelimiterSymbolsMustBeDifferent(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Delimiters symbols must be different');

        new DiContainer(linkContainerSymbol: '.', delimiterAccessArrayNotationSymbol: '.');
    }

    public function testOtherClassByArrayNotated(): void
    {
        $def = [
            'app' => [
                'admin' => [
                    'email' => 'admin@mail.com',
                ],
                'logger' => [
                    'instance' => Classes\Logger::class,
                    'file' => '/var/logs/app.log',
                    'name' => 'main-logger',
                ],
            ],
            Classes\Logger::class => [
                'arguments' => [
                    'name' => '@app.logger.name',
                    'file' => '@app.logger.file',
                ],
            ],
            Classes\SendEmail::class => [
                'arguments' => [
                    'adminEmail' => '@app.admin.email',
                    'logger' => '@app.logger.instance',
                ],
            ],
        ];

        $container = (new DiContainerFactory())->make($def);

        $class = $container->get(Classes\SendEmail::class);

        $this->assertInstanceOf(Classes\Logger::class, $class->logger);
        $this->assertEquals('/var/logs/app.log', $class->logger->file);
        $this->assertEquals('main-logger', $class->logger->name);
        $this->assertEquals('admin@mail.com', $class->adminEmail);
    }
}
