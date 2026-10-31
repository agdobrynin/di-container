---
outline: [2, 4]
---
# «Метод-приоритета» по умолчанию { #priority-by-method-default }

## Обзор { #overview }

Для случаев когда определить приоритет элемента коллекции из тега не удается, используется **«метод-приоритета» по умолчанию**.

При отсутствии в PHP классе **«метода-приоритета» по умолчанию**, элемент коллекции получит приоритет со значением `null`.

**«Метод-приоритета» по умолчанию** задаётся в параметре `$priorityDefaultMethod` [хелпер функции `diTaggedAs()`](../../php-definition/di-tagged-as.md) или в [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md).

<!--@include: _include/method_note.md-->

## Пример конфигурации { #example }

Необходимо внедрить коллекцию элементов отмеченных тегом `'tags.ordered'` в параметр `$services` через конструктор класса `\App\Services\Foo`.

::: code-group

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

:::

При этом первым элементом коллекции должен быть PHP класс `\App\Services\Qux`, остальные элементы могут иметь неопределённую очерёдность. Для получения приоритета от элемента коллекции `\App\Services\Qux` воспользуемся возможностью объявить **«метод-приоритета» по умолчанию**, для этого реализуем метод `\App\Services\Qux::getPriority()`:

<<< @/_code/Tags/src/Services/QuxPriorityMethod.php

### Объявление в файлах конфигураций { #php-definition }

В файле конфигураций `services.php` объявить внедрение коллекции из элементов отмеченных тегом `'tags.ordered'` в параметр `$servies` конструктора PHP класса `\App\Services\Foo` и задать **«метод-приоритета» по умолчанию** в параметре `$priorityDefaultMethod` [хелпер функции `diTaggedAs()`](../../php-definition/di-tagged-as.md).

В файле конфигураций `tagged_services.php` объявить тег `'tags.ordered'` через метод `bindTag()` для PHP классов.

::: code-group

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\Foo;

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                'tags.ordered',//[!code ++]
                priorityDefaultMethod: 'getPriority',//[!code ++]
            )
    );
};
```

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php //[!code highlight]

use App\Services\{Bar, Baz, Qux};

use function \Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag('tags.ordered');//[!code ++]
        
    yield diAutowire(Baz::class)
        ->bindTag('tags.ordered');//[!code ++]

    yield diAutowire(Qux::class)
        ->bindTag('tags.ordered');//[!code ++]
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

## 🚧🚧🚧🚧

Для PHP классов объявить тег `'tags.ordered'` через атрибут `\Kaspi\DiContainer\Attributes\Tag`.

**«Метод-приоритета» по умолчанию** задаётся в параметре `$priorityDefaultMethod` [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md).

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs(// [!code ++]
            'tags.ordered',// [!code ++]
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

#[Tag('tags.ordered')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.ordered', priority: 10)]// [!code ++]
final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.ordered')]// [!code ++]
final class Qux
{
    public static function getPriorityForCollection(): int
    {
        return 100;
    }

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
// вывод консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

<!--@include: ../../php-definition/_include/bind_notes.md-->
