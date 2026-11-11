---
outline: [2, 4]
title: Совпадение идентификаторов элементов в коллекции
---
### Совпадение идентификаторов элементов в коллекции { #keys-equals }

Внедряемая коллекция по умолчанию в качестве идентификатора элементов получает идентификатор контейнера [^ContainerId] этого элемента,
но через [метаданные тега](../tag-metadata.md) можно указывать пользовательские идентификаторы элементов коллекции.

При совпадении идентификаторов у элементов в коллекцию попадает элемент с более высоким [приоритетом](../collection-priority/index.md), другие элементы с таким же идентификатором будут игнорированы.

### Пример { #keys-equals-example }

Рассмотрим пример объявления тега `'tags.internal'` PHP-классам, где идентификатор элемента коллекции объявлен через метаданные тега, при этом значение идентификатора `'id_service'` совпадают для всех объявленных тегов.

Для элемента коллекции `\App\Services\Baz` используется значение приоритета `100`, а для элемента коллекции `\App\Services\Bar` значение приоритета по умолчанию `null`.

PHP-классы:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

При внедрении коллекции в параметр конструктора `$services` PHP-класса `\App\Services\Foo` результатом становится коллекция, содержащая один элемент — PHP-класс `\App\Services\Baz`. Это происходит потому, что, поскольку оба элемента в коллекции имеют одинаковый идентификатор (`'id_service'`), приоритет, назначенный классу `\App\Services\Baz` через параметр `$priority`, оказывается выше приоритета класса `\App\Services\Bar`.

#### Объявление в файлах конфигураций { #keys-equals-example-php-definition }

В файле конфигураций `services.php` объявить внедрение коллекции из элементов отмеченных тегом `'tags.internal'` в параметр конструктора `$services` через хелпер функцию `diTaggedAs()` с параметром `$key` равным `'key_as'` указывающим имя метаданных для извлечения идентификатора элемента коллекции из тега.

В файле конфигураций `tagged_services.php` объявляем тег `'tags.internal'` и метаданные в параметре `$options` PHP-классам через метод `bindTag()`[^BindTag]. Имя метаданных `'key_as'` для каждого тега указывает какой идентификатор элемента получит каждый тег.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/tagged_services.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
    echo PHP_EOL;
}
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
            servies: diTaggedAs(//[!code ++]
                tag: 'tags.internal',//[!code ++]
                key: 'key_as',//[!code ++]
            )//[!code ++]
        );
};
```

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php //[!code highlight]

use App\Services\{Bar, Baz};

use function \Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag(//[!code ++]
            'tags.internal',//[!code ++]
            options: [//[!code ++]
                'key_as' => 'id_service',//[!code warning]
            ],//[!code ++]
        );//[!code ++]

    yield diAutowire(Baz::class)
        ->bindTag(//[!code ++]
            'tags.internal',//[!code ++]
            options: [//[!code ++]
                'key_as' => 'id_service',//[!code warning]
            ],//[!code ++]
            priority: 100,//[!code warning]
        );//[!code ++]
};
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(15) "id_service"
string(16) "App\Services\Baz"
```

#### Объявление через атрибуты { #keys-equals-example-attributes }

Объявить внедрение коллекции элементов отмеченных тегом `'tags.internal'` в параметр `$services` PHP-класса `\App\Services\Foo` через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) с параметром `$key` равным `'key_as'` указывающим имя метаданных для извлечения идентификатора элемента коллекции из тега.

Объявим тег `'tags.internal'` и метаданные в параметре `$options` PHP-классам через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md). Имя метаданных `'key_as'` для каждого тега указывает какой идентификатор элемента получит каждый тег.

PHP-классы:

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
            name: 'tags.internal',// [!code ++]
            key: 'key_as',// [!code ++]
        )]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.internal',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_service',// [!code warning]
    ],// [!code ++]
)]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.internal',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_service',// [!code warning]
    ],// [!code ++]
    priority: 100,// [!code warning]
)]// [!code ++]
final class Baz
{
    // …
}
```

:::

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
    echo PHP_EOL;
}
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(15) "id_service"
string(16) "App\Services\Baz"
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->