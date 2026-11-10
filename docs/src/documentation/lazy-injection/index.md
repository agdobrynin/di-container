---
outline: [2,4]
---
# Внедрение «ленивых» объектов контейнером { #title }


## Обзор { #overview }

Ленивое внедрение зависимостей позволяет отложить создание зависимостей объекта до момента их фактического использования, а не раньше. Для реализации этого функционала контейнера используется [«Virtual Proxies» описанный в PHP документации](https://www.php.net/manual/ru/language.oop5.lazy-objects.php).

> [!IMPORTANT]
> Механизм внедрения ленивых зависимостей **доступен только для PHP 8.4 и выше**.

> [!TIP]
> **Если версия PHP ниже 8.4**, то можно объявить внедрение «ленивой» зависимости через [хелпер функцию `diProxyClosure()`](../php-definition/di-proxy-closure.md) или [атрибут `ProxyClosure()`](../attribute-definition/proxy-closure.md).

## Как использовать { #how-to-use }

Объявление PHP-класса ленивым можно сделать через [хелпер функцию `diAutowire()`](../php-definition/di-autowire.md) или [атрибут `\Kaspi\DiContainer\Attributes\Autowire`](../attribute-definition/autowire.md) передав в параметре `$isLazy` значение `true`. Объявленный ленивый PHP-класс внедряет прокси-объект реализующий «отложенную» инициализацию объекта.

## Объявление ленивого PHP-класса в контейнере { #inject-as-lazy-global }

При объявлении PHP-класса ленивым непосредственно в контейнере означает что этот класс всегда и везде внедряется в параметр метода или вызываемого типа[^CallableType] как прокси-объект, его инициализация выполняется только в момент обращения к этому PHP-классу.

PHP-классы:

::: code-group
<<< @/_code/src/Services/FooService.php
<<< @/_code/src/Services/BarService.php
<<< @/_code/src/Services/HeavyDependency.php
<<< @/_code/src/Services/BazService.php
:::

Необходимо объявить в контейнере PHP-класс `\App\Services\HeavyDependency` чтобы он внедрялся в параметры методов везде как ленивый-объект, без дополнительных объявлений.

В параметр конструктора `$heavyDependency` PHP-классов `\App\Services\FooService` и `\App\Services\BarService` должен внедрятся ленивый объект `\App\Services\HeavyDependency`. 

### Объявление в файлах конфигураций { #example-inject-as-lazy-global-php-definition }

В [файле конфигураций](../container-builder/configuration_files.md) `lazy_services.php` объявить ленивый PHP-класс `\App\Services\HeavyDependency` через хелпер функцию `diAutowire()` с указанием в параметре `$isLazy` значения `true`:

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{FooService, BarService, HeavyDependency};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->load('/app/config/lazy_services.php')//[!code ++]
    ->build();

var_dump($container->get(FooService::class));
echo PHP_EOL;
var_dump($container->get(BarService::class));
echo PHP_EOL;
var_dump($container->get(HeavyDependency::class));
```

```php [lazy_services.php]
<?php
declare(strict_types=1);

// file: /app/config/lazy_services.php [!code highlight]

use App\Services\HeavyDependency;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(//[!code highlight]
        HeavyDependency::class,//[!code highlight]
        isLazy: true,//[!code ++]
    );//[!code highlight]
};
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:
<!--@include: _include/result_lazy_global.md-->

### Объявление через атрибут { #example-inject-as-lazy-global-attribute }

Объявить ленивый PHP-класс `\App\Services\HeavyDependency` через атрибут `\Kaspi\DiContainer\Attributes\Autowire` с указанием в параметре конструктора `$isLazy` значения `true`:

::: code-group
```php [HeavyDependency.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/HeavyDependency.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(isLazy: true)]//[!code ++]
final class HeavyDependency
{
// …
```
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{FooService, BarService, HeavyDependency};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

var_dump($container->get(FooService::class));
echo PHP_EOL;
var_dump($container->get(BarService::class));
echo PHP_EOL;
var_dump($container->get(HeavyDependency::class));
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:
<!--@include: _include/result_lazy_global.md-->

## Объявление внедрения ленивого PHP-класса в параметр { #inject-lazy-arg }

Объявление внедрения ленивого PHP-класса в параметр метода или вызываемого типа означает что прокси-объект будет внедряться только для указанного параметра, для остальных случав объект PHP-класса будет получен сразу с внедрёнными зависимостями, например при получении через метод контейнера `get()`.

PHP-классы:

::: code-group
<<< @/_code/src/Services/FooService.php
<<< @/_code/src/Services/BarService.php
<<< @/_code/src/Services/HeavyDependency.php
<<< @/_code/src/Services/BazService.php
:::

Для PHP-класса `\App\Services\FooService` необходимо объявить внедрение зависимости в параметр конструктора `$heavyDependency` через поркси-объект (*ленивый-объект*). Для остальных PHP-классов внедрение зависимости PHP-класса `\App\Services\HeavyDependency` необходимо без прокси-объекта.

### Объявление в файлах конфигураций {#example-inject-lazy-arg-php-definition }

В файле конфигураций `services.php` объявить PHP-класс `\App\Services\FooService` через хелпер функцию `diAutowire()` и объявить внедрение в параметр конструктора `$heavyDependency` PHP-класса `\App\Services\HeavyDependency` как ленивого-объекта.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{FooService, BarService, HeavyDependency};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->load('/app/config/services.php')//[!code ++]
    ->build();

var_dump($container->get(FooService::class));
echo PHP_EOL;
var_dump($container->get(BarService::class));
echo PHP_EOL;
var_dump($container->get(HeavyDependency::class));
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Services\{FooService, HeavyDependency};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(FooService::class)//[!code highlight]
        ->bindArguments(//[!code highlight]
            heavyDependency: diAutowire(//[!code ++]
                HeavyDependency::class,//[!code ++]
                isLazy: true,//[!code ++]
            )//[!code ++]
        );//[!code highlight]
};
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:
<!--@include: _include/result_lazy_args.md-->

### Объявление через атрибут {#example-inject-lazy-arg-attribute }

Объявить внедрение в параметр конструктора `$heavyDependency` PHP-класса `\App\Services\FooService` PHP-класса `\App\Services\HeavyDependency` как ленивого-объекта, через атрибут `\Kaspi\DiContainer\Attributes\Autowire`:

::: code-group

```php [FooService.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/FooService.php

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

final class FooService
{
    public function __construct(
        #[Autowire(isLazy: true)]//[!code ++]
        public readonly HeavyDependency $heavyDependency
    ) {}
// …
```
:::

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{FooService, BarService, HeavyDependency};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

var_dump($container->get(FooService::class));
echo PHP_EOL;
var_dump($container->get(BarService::class));
echo PHP_EOL;
var_dump($container->get(HeavyDependency::class));
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:
<!--@include: _include/result_lazy_args.md-->

<!--@include: ../_include/term_notes.md-->
