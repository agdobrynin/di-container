<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\CustomLogger;
use Tests\FromDocs\PhpAttribute\Fixtures\MyLogger;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversNothing]
class ServiceAttributeByClassTest extends TestCase
{
    public function testResolveByServiceWithClass(): void
    {
        $definitions = static function () {
            yield diAutowire(CustomLogger::class)
                ->bindArguments(file: '/var/log/app.log')
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        $myClass = $container->get(MyLogger::class);

        self::assertEquals('/var/log/app.log', $myClass->customLogger->loggerFile());
    }
}
