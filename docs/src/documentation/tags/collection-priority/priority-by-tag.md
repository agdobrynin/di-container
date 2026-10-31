---
outline: [2, 4]
---
# Приоритет тега { #priority-by-tag }

## Обзор { #overview }

Приоритет тега указывается в параметре `$priority` метода `bindTag()`[^BindTag], или [атрибута `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

## Пример конфигурации { #example-priority-by-tag }

Необходимо внедрить коллекцию элементов отмеченных тегом `'tags.ordered'` в параметр `$services` через конструктор класса `\App\Services\Foo`.

Внедряемая коллекция должна получить следующую очерёдность элементов:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

Для объявления очерёдности в коллекции использовать **приоритет тега**.

::: code-group

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

:::


### Объявление в файлах конфигураций { #priority-by-tag-php-definition }

В [файле конфигураций](../../container-builder/configuration_files.md) `tagged_services.php` необходимо объявить тег `'tags.ordered'` для PHP классов и приоритет каждого тега в параметре `$priority` метода `bindTag()`[^BindTag].

::: code-group

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php //[!code highlight]

use App\Services\{Bar, Baz, Qux};

use function \Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag('tags.ordered');//[!code highlight]
   
   yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            priority: 10,//[!code ++]
        );

   yield diAutowire(Qux::class)
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            priority: 100,//[!code ++]
        );
};
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\Foo;

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.ordered')//[!code highlight]
        );
};
```

<<< @/_code/Tags/container_build_ordered.php [container_build.php]

::: 

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```text
string(16) "App\Services\Qux"
string(16) "App\Services\Baz"
string(16) "App\Services\Bar"
```

Порядок сортировки внедрённой коллекции обусловлен значениями `$prioriy` для каждого тега.

### Объявление через атрибуты { #priority-by-tag-attributes }

Объявить тег `'tags.ordered'` PHP классам через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) с параметром `$priority` для каждого тега. 

::: code-group

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php // [!code highlight]
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code highlight]

#[Tag(// [!code highlight]
    'tags.ordered',// [!code highlight]
    priority: 100,// [!code ++]
)]// [!code highlight]
final class Qux
{
    // …
}
```
<<< @/_code/Tags/src/Services/BazOrderedAttr.php [Baz.php]

<<< @/_code/Tags/src/Services/BarOrderedAttr.php [Bar.php]

<<< @/_code/Tags/container_build_ordered_attr.php [container_build.php]

<<< @/_code/Tags/src/Services/FooOrderedAttr.php [Foo.php]

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```text
string(16) "App\Services\Qux"
string(16) "App\Services\Baz"
string(16) "App\Services\Bar"
```

Порядок сортировки внедрённой коллекции обусловлен значениями в параметре `$priority` атрибута `\Kaspi\DiContainer\Attributes\Tag` для каждого тега.

<!--@include: ../../php-definition/_include/bind_notes.md-->
