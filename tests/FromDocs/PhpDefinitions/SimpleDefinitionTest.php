<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 *
 * @internal
 */
class SimpleDefinitionTest extends TestCase
{
    public function testSimpleDefinition(): void
    {
        $definitions = [
            'logger.name' => 'payment',
            'logger.file' => '/var/log/payment.log',
            'feedback.show-recipient' => false,
            'feedback.email' => [
                'help@my-company.inc',
                'boss@my-company.inc',
            ],
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->assertEquals('payment', $container->get('logger.name'));
        $this->assertEquals('/var/log/payment.log', $container->get('logger.file'));
        $this->assertFalse($container->get('feedback.show-recipient'));
        $this->assertEquals(['help@my-company.inc', 'boss@my-company.inc'], $container->get('feedback.email'));
    }
}
