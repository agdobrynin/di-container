---
outline: [2, 4]
title: «Метод идентификатора» элемента коллекции по умолчанию
---
### «Метод идентификатора» элемента коллекции по умолчанию { #key-from-method-default }

Для элементов коллекции представленных PHP-классом **«метод идентификатора» элемента коллекции по умолчанию** это резервный механизм получения идентификатора элемента коллекции если отсутствует конфигурация в [метаданных тега](../tag-metadata.md).

Если **«метод идентификатора» элемента коллекции по умолчанию** не реализован в PHP-классе, то значение идентификатора будет представлено идентификатором контейнера [^ContainerId] этого элемента.

Внедрение коллекции через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md)
с указанием в параметре `$keyDefaultMethod` имени метода.

<!--@include: _include/key_from_method.md-->
<!--@include: _include/important_key_equals.md-->

### Пример извлечения идентификатора элемента из «Метод идентификатора» элемента коллекции по умолчанию { #key-from-method-default-default }

PHP-классы:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

Необходимо внедрить в параметр конструктора `$services` PHP-класса `\App\Services\Foo` «ленивую коллекцию» из элементов отмеченных тегом `'tags.internal'` и указанием резервного метода `getCustomIndex()` для извлечения идентификатора контейнера элемента коллекции.

Реализуем метод `\App\Services\Baz::getCustomIndex()` возвращающий идентификатор элемента коллекции.

::: code-group
```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

final class Baz
{
    // …

    public static function getCustomIndex(): string //[!code ++]
    {//[!code ++]
        return 'baz_custom_id';//[!code ++]
    }//[!code ++]
}
```
:::

#### Объявление в файлах конфигураций { #key-from-method-default-default-php-definition }

В файле конфигураций `services.php` объявить внедрение коллекции из элементов отмеченных тегом `'tags.internal'` в параметр конструктора `$services` через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) с параметром `$keyDefaultMethod` равным `'getCustomIndex'`.

В файле конфигураций `tagged_services.php` объявляем тег `'tags.internal'` PHP-классам через метод `bindTag()`[^BindTag].

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
                keyDefaultMethod: 'getCustomIndex',//[!code ++]
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
string(16) "App\Services\Bar"

string(15) "baz_custom_id"
string(16) "App\Services\Baz"
```

#### Объявление через атрибуты { #key-from-method-default-default-attributes }

Для PHP-классов объявить тег `'tags.ordered'` через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

Объявить внедрение коллекции элементов отмеченных тегом `'tags.ordered'` в параметр `$services` конструктора класса `\App\Services\Foo` через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) с параметром `$keyDefaultMethod` равным `'getCustomIndex'`.

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
            keyDefaultMethod: 'getCustomIndex',// [!code ++]
        )]// [!code ++]
        public readonly iterable $services
    ) {}
}
```

<<< @/_code/TagsId/src/Services/BarAttr.php [Bar.php]

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;//[!code ++]

#[Tag('tags.internal')]//[!code ++]
final class Baz
{
    // …

    public static function getCustomIndex(): string//[!code ++]
    {//[!code ++]
        return 'baz_custom_id';//[!code ++]
    }//[!code ++]
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
string(16) "App\Services\Bar"
string(16) "App\Services\Bar"

string(15) "baz_custom_id"
string(16) "App\Services\Baz"
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->
