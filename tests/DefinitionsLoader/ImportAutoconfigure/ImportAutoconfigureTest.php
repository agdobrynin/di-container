<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportAutoconfigure;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ImportAutoconfigureTest extends TestCase
{
    public function testAutoconfigure(): void
    {
        $container = (new DiContainerFactory(
            new DiContainerConfig(
                useZeroConfigurationDefinition: false,
                useAttribute: false,
            )
        ))
            ->make(
                (new DefinitionsLoader())
                    ->import('Tests\\', __DIR__.'/Fixtures/')
                    ->definitions()
            )
        ;

        $this->assertFalse($container->has(Fixtures\Factories\DiFactoryPerson::class));
        $this->assertTrue($container->has(Fixtures\Person::class));

        $this->assertEquals(
            ['name' => 'Ivan', 'surname' => 'Petrov', 'age' => 22],
            (array) $container->get(Fixtures\Person::class)
        );
    }
}
