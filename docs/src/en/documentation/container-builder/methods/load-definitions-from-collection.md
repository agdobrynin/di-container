---
outline: [2,4]
title: Declaring definitions via a collection
---
### Declaring definitions via a collection { #title }

Definitions can be declared using a collection. This collection must be an `iterable` type. Its elements should be key-value pairs, where the key is a container identifier [^ContainerId] and the value is the definition declaration.

### addDefinitions()

The `addDefinitions()` method declares container definitions while ensuring that each container identifier [^ContainerId] remains unique throughout the entire container configuration.

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder::addDefinitions(
    iterable $definitions
): static;
```
Parameters:
- `$definitions` – a collection of definitions.

> [!WARNING]
> <!--@include: ../_include/build_id_equals.md-->

#### Example of using the addDefinitions() method { #example-add-definitions }

PHP classes:

::: code-group
<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php
:::

Declare PHP classes via a collection, with scalar parameters injected: `\App\Storages\ReportStorage` into the `$dir` parameter, and `\App\Services\ReportMaker` into the `$lastDays` parameter.

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
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\diAutowire;

// An anonymous function as a collection
$fn = static function (): Generator {// [!code highlight]
    yield diAutowire(ReportStorage::class)// [!code highlight]
        ->bindArguments(// [!code highlight]
            dir: '/var/data'// [!code highlight]
        );// [!code highlight]
};// [!code highlight]

// PHP array as a collection
$services = [// [!code highlight]
    diAutowire(ReportMaker::class)// [!code highlight]
        ->bindArguments(// [!code highlight]
            lastDays: 10// [!code highlight]
        )// [!code highlight]
];// [!code highlight]

$container = (new DiContainerBuilder())
    ->addDefinitions(($fn)())// [!code ++]
    ->addDefinitions($services)// [!code ++]
    ->build();


$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
var_dump($service->lastDays);
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string (9) "/var/data"
int(10)
```

### addDefinitionsOverride()

The `addDefinitionsOverride()` method registers definitions from a collection, overwriting any previously registered definitions that share the same identifier within the container[^ContainerId].

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder::addDefinitionsOverride(
    iterable $definitions
): static;
```
Parameters:
- `$definitions` – a collection of definitions.


#### Example of using the addDefinitionsOverride() method { #example-add-definitions-override }

PHP classes:

::: code-group

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

The `report_services.php` configuration file defines PHP classes into which scalar parameters are injected: a value is passed to the `$dir` parameter of `\App\Storages\ReportStorage`, and a value is passed to the `$lastDays` parameter of `\App\Services\ReportMaker`. If the `APP_ENV` environment variable is set to `'dev'`, then the scalar value `'/tmp'` must be injected into the `$dir` parameter of the `\App\Storages\ReportStorage` class (via a collection of parameters). In effect, this means overwriting a previously established definition with a new value for the same identifier within the container[^ContainerId].

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
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\diAutowire;
use function {getenv, putenv};

putenv('APP_ENV=dev');//[!code ++]

$builder = (new DiContainerBuilder())
    ->load('/app/config/report_services.php');// [!code highlight]

if ('dev' === getenv('APP_ENV')) {
    $devServices = [//[!code ++]
        diAutowire(ReportStorage::class)//[!code ++]
            ->bindArguments(dir: '/tmp')//[!code ++]
    ];//[!code ++]

    $builder->addDefinitionsOverride($devServices);//[!code ++]
}

$container = $builder->build();

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

A message like the following will appear:

```console
string (4) "/tmp"
int(10)
```

<!--@include: ../../_include/term_notes.md-->
