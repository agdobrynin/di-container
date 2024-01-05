<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Classes\NamesWithInject;

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
        $c = DiContainerFactory::make([
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
        ]);

        $class = $c->get(NamesWithInject::class);

        $this->assertEquals(['ivan', 'piter'], $class->names);
        $this->assertEquals('Washington', $class->place);
        $this->assertEquals('https://google.com', $class->site);
    }

    public function testGetParametersByDelimiterSymbolWrongKey(): void
    {
        $c = DiContainerFactory::make([
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

        $c->get(NamesWithInject::class);
    }
}