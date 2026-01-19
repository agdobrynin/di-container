<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\FooBar;

/**
 * @internal
 */
#[CoversNothing]
class InjectByCallableTest extends TestCase
{
    public function testInjectByCallable(): void
    {
        $container = (new DiContainerBuilder())
            ->addDefinitions([
                'config.secure_code' => 'abc',
            ])
            ->build()
        ;

        // Получение данных из контейнера
        $service = $container->get(FooBar::class);

        self::assertEquals('abc', $service->foo->code);
    }
}
