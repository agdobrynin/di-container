---
outline: [2,4]
title: Declaring the injection of a lazy PHP class into a parameter
---

### Declaring the injection of a lazy PHP class into a parameter { #title }

Declaring a PHP class as "lazy" for injection into a method parameter or a callable type means that a proxy object will be injected specifically for that parameter; in all other cases, for example, when retrieving the object via the container's `get()` method, the actual PHP class instance will be returned, with all its dependencies already injected.

<!--@include: _include/lazy_declare.md-->

### Example of implementing a lazy PHP class { #example }

PHP classes:

::: code-group
<<< @/_code/src/Services/FooService.php
<<< @/_code/src/Services/BarService.php
<<< @/_code/src/Services/HeavyDependency.php
<<< @/_code/src/Services/BazService.php
:::

For the PHP class `\App\Services\FooService`, the dependency `\App\Services\HeavyDependency` must be injected into the constructor parameter `$heavyDependency` via a proxy object (*lazy object*). For all other PHP classes, the dependency `\App\Services\HeavyDependency` should be injected without a proxy object.

#### Declaration in configuration files {#example-inject-lazy-arg-php-definition }

In the `services.php` configuration file, register the PHP class `\App\Services\FooService` using the `diAutowire()` helper function, and configure the injection of the `\App\Services\HeavyDependency` PHP class into the `$heavyDependency` constructor parameter as a lazy object.

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

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:
<!--@include: _include/result_lazy_args.md-->

#### Declaration via an attribute {#example-inject-lazy-arg-attribute }

Declare the `$heavyDependency` constructor parameter of the PHP class `\App\Services\FooService` — which is of type `\App\Services\HeavyDependency` – as a lazy object, using the `\Kaspi\DiContainer\Attributes\Autowire` attribute:

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

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:
<!--@include: _include/result_lazy_args.md-->

<!--@include: ../_include/term_notes.md-->
