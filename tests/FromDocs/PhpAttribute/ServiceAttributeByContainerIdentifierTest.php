<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\SuperClass;
use Tests\FromDocs\PhpAttribute\Fixtures\SuperSrv;

use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\BuildArguments
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionInvokableWrapper
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class ServiceAttributeByContainerIdentifierTest extends TestCase
{
    public function testResolveByServiceWithContainerIdentifier(): void
    {
        $definitions = [
            'services.my-srv' => diCallable(static function (SuperSrv $srv) {
                $srv->changeConfig(['aaa', 'bbb']); // какие-то дополнительные настройки.

                return $srv; // вернуть настроенный сервис.
            }),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $class = $container->get(SuperClass::class);

        $this->assertInstanceOf(SuperSrv::class, $class->my);
    }
}
