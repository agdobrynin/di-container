<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Service;

use Kaspi\DiContainer\Attributes\TaggedAs;//[!code highlight]

final class Foo
{
    public function __construct(
        #[TaggedAs(//[!code highlight]
            'tags.internal_service',//[!code highlight]
            containerIdExclude: [// [!code ++]
                Baz::class// [!code ++]
            ],// [!code ++]
        )]//[!code highlight]
        public readonly iterable $services
    ) {}
}
