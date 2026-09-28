<?php
declare(strict_types=1);

// file: /app/src/Services/FooService.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\{
    DiDefinitionGet as DiGet,
    DiDefinitionParameter as DiParameter
};
use App\Interfaces\QuxInterface;

#[Autowire(
    arguments: [// [!code ++]
        'qux' => new DiGet(BarService::class),// [!code ++]
        'adminEmail' => new DiParameter('emails.bar'),// [!code ++]
    ]// [!code ++]
)]
#[Autowire(
    id: 'services.foo_service.with_baz',// [!code highlight]
    arguments: [// [!code ++]
        'qux' => new DiGet(BazService::class),// [!code ++]
        'adminEmail' => new DiParameter('emails.baz'),// [!code ++]
    ]// [!code ++]
)]
final class FooService
{
    public function __construct(
        public readonly BatService $bat,
        public readonly QuxInterface $qux,
        public readonly string $adminEmail
    ) {}
}
