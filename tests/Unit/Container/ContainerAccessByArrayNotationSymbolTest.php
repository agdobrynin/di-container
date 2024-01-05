<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Tests\Fixtures\Classes\Names;
use Tests\Fixtures\Classes\ReportEmail;

/**
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
                    return new ReportEmail($container->get('emails.admin'), 0);
                },
            ],

            // ... more other definitions

            Names::class => [
                'arguments' => [
                    'names' => 'app.users',
                    'place' => 'app.city',
                    'site' => 'search_site',
                    'reportEmail' => 'report.reportEmail',
                ],
            ],
        ];

        $container = DiContainerFactory::make($def);

        $class = $container->get(Names::class);

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

            Names::class => [
                'arguments' => [
                    'names' => 'app.users',
                ],
            ],
        ];

        $container = DiContainerFactory::make($def);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('array notation key [app.users]');

        $container->get(Names::class);
    }

    public function testDelimiterSymbolsMustBeDifferent(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Delimiters symbols must be different');

        new DiContainer(linkContainerSymbol: '.', delimiterArrayAccessSymbol: '.');
    }
}
