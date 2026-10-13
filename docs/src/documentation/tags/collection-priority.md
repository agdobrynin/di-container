---
outline: [2, 4]
---
# ⬆️ Приоритет элементов коллекции { #title }

## Обзор { #overview }

Приоритет элементов коллекции определяет как будут отсортированы элементы. Значение приоритета представлено типами `int`, `string`, `null`.

Для сортировки используется сравнение значений приоритетов элементов коллекции. Сравнение строк происходит как последовательности байтов строк.

**Чем больше значение приоритета, тем выше сервис будет расположен в коллекции.**

Очерёдность получения приоритета у элемента коллекции в порядке значимости:

1. [Приоритет тега](#priority-by-tag).
2. [«Метод-приоритета»](#priority-by-method) тега для элемента коллекции PHP класс.
3. [«Метод-приоритета» по умолчанию](#priority-by-method-default) тега для элемента коллекции PHP класс.

> [!IMPORTANT]
> «Метод-приоритета» и «метод-приоритета» по умолчанию должен быть объявлен с модификаторами `public` и `static`. Возвращаемые значения метода – `int`, `string` или `null`.
>
> В качестве параметров метод принимает два аргумента передаваемые контейнером:
>  - `string $tag` – имя тега.
>  - `array $options` – [метаданные тега](tag-metadata.md).
>
> Эти параметры метода можно использовать построения логики выдачи приоритета тега.

## Приоритет тега { #priority-by-tag }

Приоритет тега задаётся в теге элемента коллекции.
[В файлах конфигураций](../container-builder/configuration_files.md) определений приоритет указываться в параметре `$priority` метода `bindTag()`[^BindTag],
через PHP атрибуты приоритет указывается в параметре [атрибута `\Kaspi\DiContainer\Attributes\Tag::$priority`](../attribute-definition/tag.md).

### Пример конфигурации { #example-priority-by-tag }

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

#### Настройка в файлах конфигураций { #priority-by-tag-php-definition }

Передаем значение приоритета в параметре `$priority` метода `bindTag()`[^BindTag]

Конфигурационный файл:

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.ordered')
        );

    yield diAutowire(Bar::class)
        ->bindTag(name: 'tags.ordered');
   
   yield diAutowire(Baz::class)
        ->bindTag(name: 'tags.ordered', priority: 10);

   yield diAutowire(Qux::class)
        ->bindTag(name: 'tags.ordered', priority: 100);
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/tagged_foo_service.php')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}
// вывод в консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

Порядок сортировки внедрённой коллекции обусловлен значениями `$prioriy` для каждого тега.

#### Конфигурация через атрибуты { #priority-by-tag-attributes }

Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md),
к элементам коллекции, PHP классам, [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).

PHP классы:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.ordered')]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered', priority: 10)]// [!code ++]
final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered', priority: 100)]// [!code ++]
final class Qux
{
    // …
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}
// вывод в консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

Порядок сортировки внедрённой коллекции обусловлен значениями в атрибутах `\Kaspi\DiContainer\Attributes\Tag::$prioriy` для каждого тега.

## «Метод-приоритета» { #priority-by-method }

Для элементов внедряемой коллекции представленных PHP классом приоритет в коллекции может быть задан через «метод-приоритета».

«**Метод-приоритета**» задаётся в теге элемента коллекции.

В [файлах конфигураций](../container-builder/configuration_files.md) определений «метод-приоритета» передаётся через [метаданные](tag-metadata.md) в параметре `$options` метода `bindTag()`[^BindTag].
**Имя ключа в метаданных должно быть** `'priority.method'`, значение – имя метода.

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

## «Метод-приоритета» по умолчанию { #priority-by-method-default }

Для случаев когда определить приоритет элемента коллекции из тега не удается, используется «**метод-приоритета**» по умолчанию.
При отсутствии в PHP классе «**метода-приоритета**» по умолчанию, элемент коллекции получит приоритет со значением `null`.  

В [файле конфигураций определений](../container-builder/configuration_files.md) имя метода указывается в параметре `$priorityDefaultMethod` [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md).

::: code-group

```php [файл конфигураций tagged_service.php]   
// file: /app/config/tagged_service.php
use Generator;
use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
   yield diAutowire(Foo::class)
      ->bindArguments(
         servies: diTaggedAs(
            'tags.ordered',// [!code ++]
            priorityDefaultMethod: 'getPriorityForCollection'// [!code ++]
         )
      );
      

    yield diAutowire(Bar::class)
        ->bindTag(name: 'tags.ordered');
};
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
   // …

   // 🚩 без явного указания приоритета и «метода-приоритета» в теге,
   // приоритет может быть получен через «метода-приоритета» по умолчанию
   public static function getPriorityForCollection(string $tag): string|int|null// [!code ++]
   {// [!code ++]
      return match ($tag) {// [!code ++]
         'tags.internal' => 100,// [!code ++]
         'tags.validator' => 'GROUP100:0001',// [!code ++]
         default => null,// [!code ++]
      };// [!code ++]
   }// [!code ++]
}
```

:::


Через PHP атрибуты «метод-приоритета» по умолчанию указывается в параметре атрибута `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod`.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
   public function __construct(
      #[TaggedAs(// [!code ++]
         name: 'tags.ordered',// [!code ++]
         priorityDefaultMethod: 'getPriorityForCollection',// [!code ++]
      )]// [!code ++]
      public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered')]// [!code ++]
final class Bar
{
    // …
       // 🚩 без явного указания приоритета и «метода-приоритета» в теге,
   // приоритет может быть получен через «метода-приоритета» по умолчанию
   public static function getPriorityForCollection(string $tag): string|int|null// [!code ++]
   {// [!code ++]
      return match ($tag) {// [!code ++]
         'tags.internal' => 100,// [!code ++]
         'tags.validator' => 'GROUP100:0001',// [!code ++]
         default => null,// [!code ++]
      };// [!code ++]
   }// [!code ++]
}
```

:::

<!--@include: ../php-definition/_include/bind-tag_notes.md-->
