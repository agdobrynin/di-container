<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\NamesWithInject;

class ContainerWithAttributeSymbolsTest extends TestCase
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
}
