# AutowireExclude

## Обзор { #overview }


Атрибут `\Kaspi\DiContainer\Attributes\AutowireExclude` исключает из внедряемых зависимостей контейнера PHP-класс или интерфейс. FQCN [^FQCN] будет недоступен для метода контейнера `get()` и `has()`.  

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\AutowireExclude::__construct()
```

У атрибута нет параметров.

> [!WARNING]
> Атрибут `\Kaspi\DiContainer\Attributes\AutowireExclude` исключает из конфигурирования любые другие атрибуты.

## Пример исключения PHP-класса { #example }

PHP-классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

final class Foo
{
    // …
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

:::

Исключить из контейнера PHP-класс `\App\Services\Foo` через атрибут `\Kaspi\DiContainer\Attributes\AutowireExclude`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\AutowireExclude;

#[AutowireExclude]// [!code ++]
final class Foo
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
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

var_dump($container->has(Foo::class));
var_dump($container->has(Bar::class));
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
(bool) false
(bool) true
```

<!--@include: ../_include/term_notes.md-->

