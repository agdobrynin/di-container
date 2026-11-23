---
outline: [2,4]
title: Declaring a lazy PHP class in a container
---
### Declaring a lazy PHP class in a container { #title }

Declaring a PHP class as "lazy" directly within the container means that, whenever it is injected, whether as a method parameter or into a callable type[^CallableType], it is provided as a proxy object; the actual initialization of the PHP class occurs only at the moment it is accessed.

<!--@include: _include/lazy_declare.md-->

### Example of a lazy PHP class declaration { #example }

PHP classes:

::: code-group
<<< @/_code/src/Services/FooService.php
<<< @/_code/src/Services/BarService.php
<<< @/_code/src/Services/HeavyDependency.php
<<< @/_code/src/Services/BazService.php
:::

The PHP class `\App\Services\HeavyDependency` must be registered in the container so that it is injected into method parameters, wherever it is used, as a "lazy" object, without requiring any additional declarations.

A lazy instance of `\App\Services\HeavyDependency` should be injected into the `$heavyDependency` constructor parameter of the PHP classes `\App\Services\FooService` and `\App\Services\BarService`.

#### Declaration in configuration files { #example-inject-as-lazy-global-php-definition }

In the `lazy_services.php` [configuration file](../container-builder/configuration_files.md), declare the lazy PHP class `\App\Services\HeavyDependency` using the `diAutowire()` helper function, with the `$isLazy` parameter set to `true`:

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

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:
<!--@include: _include/result_lazy_global.md-->

#### Declaration via an attribute { #example-inject-as-lazy-global-attribute }

Declare a lazy PHP class `\App\Services\HeavyDependency` using the `\Kaspi\DiContainer\Attributes\Autowire` attribute, with the `$isLazy` constructor parameter set to `true`:

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

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

<!--@include: _include/result_lazy_global.md-->

<!--@include: ../_include/term_notes.md-->
