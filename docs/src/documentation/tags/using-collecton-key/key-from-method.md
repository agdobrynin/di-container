---
outline: [2, 4]
---
# «Метод идентификатора» элемента коллекции { #key-from-method }

## Обзор { #overview }

Идентификатор элемента коллекции, представленного PHP-классом, можно получить через специальный метод этого PHP-класса – «метод идентификатора». Объявление такого метода производится через [метаданные тега](../tag-metadata.md).

Значение в метаданных тега указывается парой ключ-значение:
- **ключ** – имя метаданных в теге.
- **значение** – «метод идентификатора» с префиксом `self::`.

Внедрение коллекции через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md)
с передачей **имени метаданных** в параметре `$key`.

<!--@include: _include/key_in_metadata_defined.md-->

Метаданные тега передаются в параметре `$options` метода `bindTag()` [^BindTag] или [атрибута `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

<!--@include: _include/key_from_method.md-->

<!--@include: _include/important_key_equals.md-->

## Пример извлечения идентификатора элемента из «Метод идентификатора» { #example-key-from-method }

PHP-классы:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

Необходимо внедрить в параметр конструктора `$services` PHP-класса `\App\Services\Foo` «ленивую коллекцию» из элементов отмеченных тегом `'tags.internal'`, где идентификатор элемента коллекции для PHP-класса `\App\Services\Bar` должен использовать «метод идентификатора» `\App\Services\Baz::getKey()` и будет объявлен в метаданных тега под именем `'key_as'`.

Реализуем метод `\App\Services\Baz::getKey()` возвращающий идентификатор элемента коллекции.

::: code-group
```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

final class Baz
{
    // …
    
    public static function getKey(string $tag, array $options): string//[!code ++]
    {//[!code ++]
        return 'id_baz';//[!code ++]
    }//[!code ++]
}
```
:::

### Объявление в файлах конфигураций { #example-key-from-method-php-definitions }

В файле конфигураций `services.php` объявить внедрение коллекции из элементов отмеченных тегом `'tags.internal'` в параметр конструктора `$services` через хелпер функцию `diTaggedAs()` с параметром `$key` равным `'key_as'`.

В файле конфигураций `tagged_services.php` объявляем тег `'tags.internal'` PHP-классам через метод `bindTag()`, а для PHP-класса `\App\Services\Baz` объявим дополнительно метаданные в параметре `$options`.

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
        ->bindTag('tags.internal');//[!code ++]

    yield diAutowire(Baz::class)
        ->bindTag(//[!code ++]
            'tags.internal',//[!code ++]
            options: [//[!code ++]
                'key_as' => 'self::getKey',//[!code ++]
            ],//[!code ++]
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
string(16) "App\Services\Bar"
string(16) "App\Services\Bar"

string(15) "id_baz"
string(16) "App\Services\Baz"
```

### Объявление через атрибуты { #example-key-from-method-php-attributes }

Объявить внедрение коллекции из элементов отмеченных тегом `'tags.internal'` в параметр конструктора `$services` через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) с параметром `$key` равным `'key_as'`.

Объявим тег `'tags.internal'` PHP-классам через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md), а для PHP-класса `\App\Services\Baz` объявим дополнительно метаданные в параметре `$options`.

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

<<< @/_code/TagsId/src/Services/BarAttr.php [Bar.php]

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.internal',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'self::getKey',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Baz
{
    // …
    
    public static function getKey(string $tag, array $options): string
    {
        return 'id_baz';
    }
}
```
:::

::: code-group

```php
[container_build.php]
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
string(16) "App\Services\Bar"
string(16) "App\Services\Bar"

string(15) "id_baz"
string(16) "App\Services\Baz"
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->