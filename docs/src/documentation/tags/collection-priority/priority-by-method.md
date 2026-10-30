---
outline: [2, 4]
---
# «Метод-приоритета» { #priority-by-method }

## Обзор { #overview }

Для элементов внедряемой коллекции представленных PHP классом приоритет в коллекции может быть объявлен через «метод-приоритета».

При объявлении тега в [файлах конфигураций](../../container-builder/configuration_files.md) «метод-приоритета» объявляется через [метаданные](../tag-metadata.md) в параметре `$options` метода `bindTag()`[^BindTag].
**Имя метаданных должно быть** представлено строкой `'priority.method'`, значение – имя метода.

При объявлении тега через атрибут `\Kaspi\DiContainer\Attributes\Tag` «метод-приоритета» указывается в параметре `$priorityMethod`.

<!--@include: _include/method_note.md-->

## Пример конфигурации { #example }

Необходимо внедрить коллекцию элементов отмеченных тегом `'tags.ordered'` в параметр `$services` через конструктор класса `\App\Services\Foo`.

Внедряемая коллекция должна получить следующую очерёдность элементов:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

Элемент коллекции `\App\Services\Qux` должен использовать «**метод-приоритета**» `\App\Services\Qux::getPriority()` для объявления приоритета тега, для остальных элементов через [приоритет тега](priority-by-tag.md).

::: code-group

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/QuxPriorityMethod.php [Qux.php]

:::

### Объявление в файлах конфигураций { #php-definition }

В [файле конфигураций](../../container-builder/configuration_files.md) `tagged_services.php` для PHP класса `\App\Services\Qux` объявить тег `'tags.ordered'` и «метод-приоритета» через [метаданные](../tag-metadata.md) в параметре `$options` метода `bindTag()`[^BindTag], для остальных классов объявить тег `'tags.ordered'` и его приоритет в параметре `$priority` метода `bindTag()`[^BindTag].

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
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            priority: 10,//[!code ++]
        );

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

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/QuxPriorityMethod.php [Qux.php]

:::

### Объявление через атрибуты { #attributes }

Объявить тег `'tags.ordered'` для PHP класса `\App\Services\Qux` и «метод-приоритета» в параметре `$priorityMethod` атрибута `\Kaspi\DiContainer\Attributes\Tag`. Для остальных PHP классов объявить тег `'tags.ordered'` и приоритет в параметре `$priority` атрибута `\Kaspi\DiContainer\Attributes\Tag`.

::: code-group

<<< @/_code/Tags/src/Services/QuxPriorityMethodAttr.php [Qux.php]

<<< @/_code/Tags/src/Services/BarOrderedAttr.php [Bar.php]

<<< @/_code/Tags/src/Services/BazOrderedAttr.php [Baz.php]

<<< @/_code/Tags/container_build_ordered_attr.php [container_build.php]

<<< @/_code/Tags/src/Services/FooOrderedAttr.php [Foo.php]

:::

<!--@include: ../../php-definition/_include/bind_notes.md-->
