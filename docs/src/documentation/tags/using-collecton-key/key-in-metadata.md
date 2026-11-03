---
outline: [2, 4]
---
# Идентификатор элемента в метаданных тега { #key-in-metadata }

## Обзор { #overview } 

Пользовательские идентификаторы элементам коллекции можно установить через [метаданные](../tag-metadata.md) тега.

Значение в метаданных тега указывается парой ключ-значение:

- **ключ** – имя метаданных в теге.
- **значение** – идентификатор элемента коллекции.

Внедрение коллекции через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md)
с передачей **имени метаданных** в параметре `$key`.

<!--@include: _include/key_in_metadata_defined.md-->

Метаданные тега передаются в параметре `$options` метода `bindTag()` [^BindTag] или [атрибута `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

<!--@include: _include/important_key_equals.md-->

## Пример извлечения идентификатора элемента из метаданных { #example-key-in-metadata }

PHP-классы:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

Необходимо внедрить в параметр конструктора `$services` PHP-класса `\App\Services\Foo` «ленивую коллекцию» из элементов отмеченных тегом `'tags.internal'`, где идентификатор элемента коллекции для PHP-класса `\App\Services\Bar` должен быть представлен строкой `'id_bar'` и будет объявлен в метаданных тега под именем `'key_as'`.

### Объявление в файлах конфигураций { #example-key-in-metadata-php-definition }

В файле конфигураций `services.php` объявить внедрение коллекции из элементов отмеченных тегом `'tags.internal'` в параметр конструктора `$services` через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) с параметром `$key` равным `'key_as'`.

В файле конфигураций `tagged_services.php` объявляем тег `'tags.internal'` PHP-классам через метод `bindTag()`[^BindTag], а для PHP-класса `\App\Services\Bar` объявим дополнительно метаданные в параметре `$options`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Baz};

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/tagged_services.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);
$bar = $foo->services->get('id_bar');
$baz = $foo->services->get(Baz::class);

var_dump($bar::class);
var_dump($baz::class);
var_dump(count($foo->services));
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
                'key_as' => 'id_bar',//[!code ++]
            ]//[!code ++]
        );//[!code ++]

    yield diAutowire(Baz::class)
        ->bindTag('tags.internal');//[!code ++]
};
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
int(2)
```

### Объявление через атрибуты { #example-key-in-metadata-attributes }

Объявить внедрение коллекции элементов отмеченных тегом `'tags.internal'` в параметр `$services` PHP-класса `\App\Services\Foo` через атрибут [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) с параметром `$key` равным `'key_as'`.

Объявим тег `'tags.internal'` PHP-классам через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md), а для PHP-класса `\App\Services\Bar` объявим дополнительно метаданные в параметре `$options`.

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
        'key_as' => 'id_bar',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Bar
{
    // …
}
```

<<< @/_code/TagsId/src/Services/BazAttr.php [Baz.php]

:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);
$bar = $foo->services->get('id_bar');
$baz = $foo->services->get(Baz::class);

var_dump($bar::class);
var_dump($baz::class);
var_dump(count($foo->services));
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
int(2)
```

<!--@include: ../../php-definition/_include/bind_notes.md-->
<!--@include: ../../_include/term_notes.md-->
