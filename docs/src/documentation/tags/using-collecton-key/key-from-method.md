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
        return 'id_baz_staging';//[!code ++]
    }
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
use App\Services\{Foo, Bar, Baz};

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

string(15) "id_baz_staging"
string(16) "App\Services\Baz"
```

### Конфигурация через атрибуты { #example-key-from-method-php-attributes }

🚧🚧🚧🚧

Внедрение коллекции через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) и передачей имени метаданных в параметр `$key`.

К PHP-классам которые будут элементам коллекции применим [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) с метаданными.


PHP-классы:

::: code-group

```php [Foo.php]
//file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs(// [!code ++]
            name: 'tags.qux',// [!code ++]
            key: 'key_as',// [!code ++]
        )]// [!code ++]
        public readonly iterable $services
    ) {}
}
```

```php [Bar.php]
//file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.qux',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_bar',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.qux',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'self::getKeyThroughOptions',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Baz
{
    // …
    
    public static function getKeyThroughOptions(string $tag, array $options): string
    {
        $isStaging = $options['app_staging'] ?? false;

        if (true === $isStaging) {
            return 'id_baz_staging';
        }
        
        return 'id_baz';
    }
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

// перебор коллекции
foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
}
// вывод в консоли:
//string(6) "id_bar"
//string(16) "App\Services\Bar"
//string(6) "id_baz"
//string(16) "App\Services\Baz"


// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has('id_bar'),
    $foo->services->get('id_bar') instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services['id_baz']),
    $foo->services['id_baz'] instanceof Baz,
);
// bool(true)
// bool(true)
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->