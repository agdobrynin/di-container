<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
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
