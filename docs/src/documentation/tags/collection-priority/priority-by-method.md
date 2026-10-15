---
outline: [2, 4]
---
# «Метод-приоритета» { #priority-by-method }

## Обзор { #overview }

Для элементов внедряемой коллекции представленных PHP классом приоритет в коллекции может быть задан через «метод-приоритета».

«**Метод-приоритета**» задаётся в теге элемента коллекции.

<!--@include: _include/method_note.md-->

## Пример конфигурации { #example }

Необходимо внедрить коллекцию в параметр `\App\Services\Foo::$services` в определенном порядке:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

PHP классы:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(public readonly iterable $services) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

final class Qux
{
    // …
}
```

:::

Совместить конфигурирование через $priority и метод

🚧🚧🚧🚧🚧🚧


В [файлах конфигураций](../../container-builder/configuration_files.md) определений «метод-приоритета» передаётся через [метаданные](../tag-metadata.md) в параметре `$options` метода `bindTag()`[^BindTag].
**Имя метаданных должно быть** `'priority.method'`, значение – имя метода.

::: code-group

```php [файл конфигураций tagged_service.php]   
// file: /app/config/tagged_service.php
use Generator;
use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
   yield diAutowire(Foo::class)
      ->bindArguments(
         servies: diTaggedAs('tags.ordered')
      );

    yield diAutowire(Bar::class)
        ->bindTag(
            name: 'tags.ordered',
            options: [
               'priority.method' => 'getPriority',// [!code ++]
            ]
        );
};
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
    public static function getPriority(string $tagName, array $options): int // [!code ++]
    {// [!code ++]
        return 100;// [!code ++]
    }// [!code ++]
}
```

:::


Через PHP атрибуты «метод-приоритета» указывается в параметре атрибута `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod`.

```php
// file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.ordered',// [!code ++]
    priorityMethod: 'getPriority',// [!code ++]
)]// [!code ++]
final class Bar
{
    // …
    public static function getPriority(string $tagName, array $options): int // [!code ++]
    {// [!code ++]
        return 100;// [!code ++]
    }// [!code ++]  
}
```

<!--@include: ../../php-definition/_include/bind-tag_notes.md-->
