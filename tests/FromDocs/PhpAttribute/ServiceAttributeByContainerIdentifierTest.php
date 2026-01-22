<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\SuperClass;
use Tests\FromDocs\PhpAttribute\Fixtures\SuperSrv;

use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversNothing]
class ServiceAttributeByContainerIdentifierTest extends TestCase
{
    public function testResolveByServiceWithContainerIdentifier(): void
    {
        $definitions = static function () {
            yield 'services.my-srv' => diCallable(static function (SuperSrv $srv) {
                $srv->changeConfig(['aaa', 'bbb']); // какие-то дополнительные настройки.

                return $srv; // вернуть настроенный сервис.
            });
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        $class = $container->get(SuperClass::class);

        self::assertInstanceOf(SuperSrv::class, $class->my);
    }
}
