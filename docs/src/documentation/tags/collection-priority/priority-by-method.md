---
outline: [2, 4]
---
# «Метод-приоритета» тега { #priority-by-method }

## Обзор { #overview }

Для элементов внедряемой коллекции представленных PHP классом приоритет в коллекции может быть объявлен через «метод-приоритета».

При объявлении тега в [файлах конфигураций](../../container-builder/configuration_files.md) «метод-приоритета» объявляется через [метаданные](../tag-metadata.md) в параметре `$options` метода `bindTag()`[^BindTag].
**Имя метаданных должно быть** представлено строкой `'priority.method'`, значение – имя метода.

При объявлении тега через атрибут `\Kaspi\DiContainer\Attributes\Tag` «метод-приоритета» указывается в параметре `$priorityMethod`.

<!--@include: _include/method_note.md-->

## Пример конфигурации { #example }

Необходимо внедрить коллекцию элементов отмеченных тегом `'tags.ordered'` в параметр `$services` через конструктор класса `\App\Services\Foo`.

::: code-group

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

:::

При этом первым элементом коллекции должен быть PHP класс `\App\Services\Qux`, остальные элементы могут иметь неопределённую очерёдность. Для получения приоритета от элемента коллекции `\App\Services\Qux` воспользуемся возможностью объявить «метод-приоритета» для элемента коллекции, для этого реализуем метод `\App\Services\Qux::getPriority()`:

<<< @/_code/Tags/src/Services/QuxPriorityMethod.php


### Объявление в файлах конфигураций { #php-definition }

В [файле конфигураций](../../container-builder/configuration_files.md) `tagged_services.php` для PHP класса `\App\Services\Qux` объявить тег `'tags.ordered'` и «метод-приоритета» через [метаданные](../tag-metadata.md) в параметре `$options` метода `bindTag()`[^BindTag], для остальных классов объявить тег `'tags.ordered'` через метод `bindTag()`.

**Имя метаданных для «метода-приоритета» должно быть** представлено строкой `'priority.method'`, значение – `'getPriority'`.


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
        ->bindTag(name: 'tags.ordered');//[!code highlight]

   yield diAutowire(Qux::class)
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            options: [//[!code ++]
               'priority.method' => 'getPriority',//[!code ++]
            ]//[!code ++]
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
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
```

### Объявление через атрибуты { #attributes }

Объявить тег `'tags.ordered'` для PHP класса `\App\Services\Qux` и «метод-приоритета» в параметре `$priorityMethod` атрибута `\Kaspi\DiContainer\Attributes\Tag`. Для остальных PHP классов объявить тег `'tags.ordered'` через атрибут `\Kaspi\DiContainer\Attributes\Tag`.

::: code-group

<<< @/_code/Tags/src/Services/QuxPriorityMethodAttr.php [Qux.php]

<<< @/_code/Tags/src/Services/BarOrderedAttr.php [Bar.php]

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;//[!code highlight]

#[Tag('tags.ordered')]//[!code highlight]
final class Baz
{
    // …
}
```

<<< @/_code/Tags/src/Services/FooOrderedAttr.php [Foo.php]

:::

Контейнер зависимостей:

<<< @/_code/Tags/container_build_ordered_attr.php [container_build.php]

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```text
string(16) "App\Services\Qux"
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
```

<!--@include: ../../php-definition/_include/bind_notes.md-->
