<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Classes\Names;

/**
 * @internal
 *
 * @coversNothing
 */
class ContainerSymbolsTest extends TestCase
{
    public function testDelimiterSymbolsMustBeDifferent(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Delimiters symbols must be different');

        new DiContainer(linkContainerSymbol: '.', delimiterLevelSymbol: '.');
    }

    public function testAccessForSimpleArray(): void
    {
        $def = [
            'app' => [
                'users' => ['Ivan', 'Piter'],
                'city' => 'Washington',
            ],
            'google' => 'https://www.google.com',
            'search_site' => '@google',

            // ... more other definitions

            Names::class => [
                'arguments' => [
                    'names' => 'app.users',
                    'place' => 'app.city',
                    'site' => 'search_site',
                ],
            ],
        ];

        $container = DiContainerFactory::make($def);

        $class = $container->get(Names::class);

        $this->assertEquals(['Ivan', 'Piter'], $class->names);
        $this->assertEquals('Washington', $class->place);
        $this->assertEquals('https://www.google.com', $class->site);
    }
}
