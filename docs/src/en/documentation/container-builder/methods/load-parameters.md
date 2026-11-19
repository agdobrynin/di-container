---
outline: [2,4]
title: Registration of container parameters
---
### Registration of container parameters { #title }

The configuration of container parameters can be represented as a set of key-value pairs, where the key is the string name of the parameter, and the value is one of the [supported types](../../container-parameters/index.md#parameter-support-types).

> [!IMPORTANT]
> Any parameters previously added to the configuration can be replaced with new values, provided the parameter name matches.

#### Example { #example }

PHP classes:

::: code-group

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

In the `report_parameters.php` configuration file, specify scalar values for the container parameters:
- `'params.report_output_dir'` — the directory for saving reports.
- `'params.last_days'` — the number of recent days to be taken into account when generating a report.

The `report_services_with_params.php` configuration file defines the PHP classes to which these scalar values are passed via container parameters.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

$container = (new DiContainerBuilder())
    ->load('/app/config/report_services_with_params.php')// [!code highlight]
    ->loadParameters('/app/config/report_parameters.php')// [!code ++]
    ->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
var_dump($service->lastDays);
```

<<< @/_code/config/report_parameters.php

<<< @/_code/config/report_services_with_params.php

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

> [!NOTE]
> Read the chapter [“Container parameters”](../../container-parameters/index.md)».

### loadParameters()

The `loadParameters()` method registers container parameters from files:

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::loadParameters(
    string $file,
    string ...$_
): static
```
Parameters:

- `$file` – full path to the file.
- `$_` – full path to the file.

### addParameters()

The `addParameters()` method registers container parameters from a collection.

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::addParameters(
    iterable $params
): static
```

Parameters:
- `$params` – key-value collection of parameters configuration.

### setParameter()

The `setParameter()` method registers a container parameter and its value.

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::setParameter(
    string $name,
    array|int|float|string|bool|null|\UnitEnum $value
): static
```

Parameters:
- `$name` – the name of the parameter.
- `$value` – the value of the parameter.

<!--@include: ../../_include/term_notes.md-->
