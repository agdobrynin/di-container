---
outline: [2,4]
title: Declaring definitions in configuration files
---
### Declaring definitions in configuration files { #title }

[Configuration files](../configuration_files.md) help to separate and group container definitions. By using different configuration files, you can selectively include the ones you need based on specific conditions and pass them into the container build process.

### load()

The `load()` method registers definitions in the container based on configuration files, while simultaneously ensuring that the identifiers within the container remain unique [^ContainerId].

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder::load(
    string $file,
    string ...$_
): static;
```
Parameters:
- `$file` – full path to the configuration file.
- `$_` – full path to the configuration file.

> [!WARNING]
> <!--@include: ../_include/build_id_equals.md-->

#### Example of using the load() method { #example-load }

PHP classes:

::: code-group

<<< @/_code/src/Storages/ReportStorage.php

<<< @/_code/src/Services/ReportMaker.php

:::

In the `report_services.php` configuration file, PHP classes are defined using scalar dependency injection: `\App\Storages\ReportStorage` is injected into the `$dir` parameter, and `\App\Services\ReportMaker` is injected into the `$lastDays` parameter.

> [!TIP]
> <!--@include: _include/tip_use_di-autowire.md-->

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

$container = (new DiContainerBuilder())
    ->load('/app/config/report_services.php')//[!code ++]
    ->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
var_dump($service->lastDays);
```

<<< @/_code/config/report_services.php

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message similar to the following will appear:

```console
string (9) "/var/data"
int(10)
```

### loadOverride()

In some cases, it may be necessary to override previously established definitions associated with the same container identifier [^ContainerId].

The `loadOverride()` method declares container definitions from configuration files, overwriting any previously defined entries that share the same identifier.

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder::loadOverride(
    string $file,
    string ...$_
): static;
```

Parameters:
- `$file` – full path to the configuration file.
- `$_` – full path to the configuration file.

#### Example of using the loadOverride() method { #example-load-override }

PHP classes:

::: code-group

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

In the `report_services.php` configuration file, PHP classes are defined with scalar dependency injection: `\App\Storages\ReportStorage` receives a value for the `$dir` parameter, and `\App\Services\ReportMaker` receives a value for the `$lastDays` parameter. If the `APP_ENV` environment variable is set to `'dev'`, then scalar dependency injection for these classes must be redefined with different values—effectively overwriting the previously declared definitions for the same container identifiers[^ContainerId].

> [!TIP]
> <!--@include: _include/tip_use_di-autowire.md-->

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

use function {getenv, putenv};


putenv('APP_ENV=dev');//[!code ++]

$builder = (new DiContainerBuilder())
    ->load('/app/config/report_services.php');// [!code highlight]

if ('dev' === getenv('APP_ENV')) {//[!code ++]
    $builder->loadOverride('/app/config/dev/services.php');//[!code ++]
}//[!code ++]

$container = $builder->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
var_dump($service->lastDays);
```

<<< @/_code/config/report_services.php

```php [dev/services.php]
<?php
declare(strict_types=1);

// file: /app/config/dev/services.php // [!code highlight]

use App\Storages\ReportStorage;
use App\Services\ReportMaker;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(ReportMaker::class)
        ->bindArguments(
            lastDays: 1
        );

    yield diAutowire(ReportStorage::class)
        ->bindArguments(
            dir: '/tmp'
        );
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string (4) "/tmp"
// int(1)
```

<!--@include: ../../_include/term_notes.md-->
