<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\FromDocs\PhpDefinitions\Fixtures\ServiceLocation;

/**
 * @internal
 */
#[CoversNothing]
class ResolveByArgumentNameTest extends TestCase
{
    public function testResolveByArgumentNameFail(): void
    {
        $container = (new DiContainerBuilder())
            ->addDefinitions([
                'locationCity' => 'Vice city',
            ])
            ->build()
        ;

        try {
            $container->get(ServiceLocation::class);
        } catch (ContainerExceptionInterface $e) {
            self::assertInstanceOf(ArgumentBuilderExceptionInterface::class, $e);
            self::assertMatchesRegularExpression('/Cannot build argument via type hint for Parameter #0 \[ <required> string \$locationCity ] in .+__construct\(\)\./', $e->getMessage());

            self::assertInstanceOf(AutowireExceptionInterface::class, $e->getPrevious());
            self::assertStringContainsString('Cannot automatically resolve dependency in', $e->getPrevious()->getMessage());
        }
    }
}
