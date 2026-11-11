---
outline: [2-4]
---

# Объявление внедрения ленивого PHP-класса в параметр { #title }

Объявление внедрения ленивого PHP-класса в параметр метода или вызываемого типа означает что прокси-объект будет внедряться только для указанного параметра, для остальных случав объект PHP-класса будет получен сразу с внедрёнными зависимостями, например при получении через метод контейнера `get()`.

<!--@include: _include/lazy_declare.md-->

## Пример внедрения ленивого PHP-класса { #example }

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
