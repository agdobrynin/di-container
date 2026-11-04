---
outline: [2, 4]
---
# Идентификатор элемента как целое число { #key-as-int }

## Обзор

Внедряемая коллекция может быть представлен простым списком, где идентификаторы элементов представлены целыми числами от `0` до `n`.

При объявлении внедрения коллекции-списка необходимо параметру `$useKeys` установить значение `false` в [хелпер функции `diTaggedAs()`](../../php-definition/di-tagged-as.md) или [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md).

> [!WARNING]
> Объявление внедрения коллекции-списока означает что другие параметры хелпер-функции или атрибута отвечающие за извлечение идентификаторов элементов коллекции будут проигнорированы.  

## Пример внедрения коллекции-списка { #example }

PHP-классы:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

Необходимо внедрить в параметр конструктора `$services` PHP-класса `\App\Services\Foo` коллецию-список, где идентификаторы элементов коллекции представлены целыми числами от `0` до `n`.

### Объявление в файлах конфигураций { #example-php-definition }

В файле конфигураций `services.php` объявить внедрение коллекции из элементов отмеченных тегом `'tags.internal'` в параметр конструктора `$services`  через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) с параметром `$useKeys` равным `false`.

В файле конфигураций `tagged_services.php` объявляем тег `'tags.internal'` PHP-классам через метод `bindTag()` [^BindTag].

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

use App\Services\Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(//[!code ++]
                tag: 'tags.internal',//[!code ++]
                useKeys: false,//[!code ++]
            )//[!code ++]
        );
};
```

<<< @/_code/TagsId/config/tagged_services.php

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
int(0)
string(16) "App\Services\Bar"

int(1)
string(16) "App\Services\Baz"
```

### Объявление через атрибуты { #example-attributes }

Объявить внедрение коллекции элементов отмеченных тегом `'tags.internal'` в параметр `$services` PHP-класса `\App\Services\Foo` через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) с параметром `$useKeys` равным `false`.

Объявим тег `'tags.internal'` PHP-классам через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

PHP-классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs(// [!code ++]
            'tags.internal',// [!code ++]
            useKeys: false,// [!code ++]
        )]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

<<< @/_code/TagsId/src/Services/BarAttr.php [Bar.php]
<<< @/_code/TagsId/src/Services/BazAttr.php [Baz.php]
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};

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
int(0)
string(16) "App\Services\Bar"

int(1)
string(16) "App\Services\Baz"
```

<!--@include: ../../php-definition/_include/bind_notes.md-->
