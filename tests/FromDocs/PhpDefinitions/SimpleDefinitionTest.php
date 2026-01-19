<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
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

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions)
            ->build()
        ;

        self::assertEquals('payment', $container->get('logger.name'));
        self::assertEquals('/var/log/payment.log', $container->get('logger.file'));
        self::assertFalse($container->get('feedback.show-recipient'));
        self::assertEquals(['help@my-company.inc', 'boss@my-company.inc'], $container->get('feedback.email'));
    }
}
