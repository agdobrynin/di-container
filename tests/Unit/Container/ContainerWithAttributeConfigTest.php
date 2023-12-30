<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes\Lorem;
use Tests\Fixtures\Attributes\SimpleDb;
use Tests\Fixtures\Attributes\SimpleDbInterface;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerFactory
 */
class ContainerWithAttributeConfigTest extends TestCase
{
    public function testGetServiceByInterface(): void
    {
        $c = DiContainerFactory::make([
            'db_dsn' => 'sqlite::memory:',
            'config-table-name' => 'log',
        ]);

        /** @var Lorem $l */
        $l = $c->get(Lorem::class);

        $this->assertInstanceOf(Lorem::class, $l);
        $this->assertInstanceOf(SimpleDbInterface::class, $l->simpleDb);
        $this->assertEquals('insert Ivan into table log', $l->simpleDb->insert('Ivan'));
        $this->assertEquals(['name' => 'Piter', 'table' => 'log'], $l->simpleDb->select('Piter'));
    }

    public function testInjectPslPdo(): void
    {
        $c = DiContainerFactory::make([
            'db_dsn' => 'sqlite::memory:',
            'config-table-name' => 'log',
        ]);

        $this->assertInstanceOf(\PDO::class, $c->get(SimpleDb::class)->pdo);
    }
}
