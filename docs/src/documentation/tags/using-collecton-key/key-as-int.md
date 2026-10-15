---
outline: [2, 4]
---
# Идентификатор элемента как целое число { #key-as-int }

## Обзор

Внедряемая коллекция может быть представлен простым списком, где идентификаторы элементов представлены целыми числами от `0` до `n`.

В [хелпер функции `diTaggedAs()`](../../php-definition/di-tagged-as.md) указать значение `false` в параметре `$useKeys`:

🚧🚧🚧🚧 Добавить пример как в остальных раздела!!!

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                tag: 'tags.access_by_key',
                useKeys: false,// [!code ++]
            )
        );

    // ...
};
```

Указать значение `false` в параметре [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs::$useKeys`](../../attribute-definition/tagged-as.md):

```php
//file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Foo
{
    public function __construct(
        #[TaggedAs(
            name: 'tags.access_by_key',
            useKeys: false,// [!code ++]
        )]
        public readonly iterable $services
    ) {}    
}
```
