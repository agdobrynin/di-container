---
outline: [2,4]
title: Объявление ленивого PHP-класса в контейнере
---
### Объявление ленивого PHP-класса в контейнере { #title }

При объявлении PHP-класса ленивым непосредственно в контейнере означает что этот класс всегда и везде внедряется в параметр метода или вызываемого типа[^CallableType] как прокси-объект, его инициализация выполняется только в момент обращения к этому PHP-классу.

<!--@include: _include/lazy_declare.md-->

### Пример объявления ленивого PHP-класса { #example }

PHP-классы:

::: code-group
<<< @/_code/src/Services/FooService.php
<<< @/_code/src/Services/BarService.php
<<< @/_code/src/Services/HeavyDependency.php
<<< @/_code/src/Services/BazService.php
:::

Необходимо объявить в контейнере PHP-класс `\App\Services\HeavyDependency` чтобы он внедрялся в параметры методов везде как ленивый-объект, без дополнительных объявлений.

В параметр конструктора `$heavyDependency` PHP-классов `\App\Services\FooService` и `\App\Services\BarService` должен внедрятся ленивый объект `\App\Services\HeavyDependency`.

#### Объявление в файлах конфигураций { #example-inject-as-lazy-global-php-definition }

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

#### Объявление через атрибут { #example-inject-as-lazy-global-attribute }

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

<!--@include: ../_include/term_notes.md-->
