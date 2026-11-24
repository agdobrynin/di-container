---
outline: [2,4]
title: Registering PHP classes in a container from the file system
---
### Registering PHP classes in a container from the file system { #title }

PHP classes and interfaces can be automatically registered in the container by scanning the file system. When analyzing PHP classes and interfaces from the file system, all attributes applied to them are taken into account (see [using PHP attributes](../../container-config/index.md#use-attribute)).

### import()

The `import()` method declares PHP classes from the specified file system directories.

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder::import(
    string $namespace,
    string $src,
    array $excludeFiles = [],
    array $availableExtensions = ['php'],
): static;
```

Parameters:

- `$namespace` – the namespace prefix for PHP classes.
- `$src` – the source directory.
- `$excludeFiles` – PHP classes located in these files should be excluded from the declaration.
- `$availableExtensions` – file extensions for PHP classes.

> [!NOTE]
> The `$excludeFiles` parameter uses the pattern syntax from the [PHP function `\fnmatch()`](https://www.php.net/manual/en/function.fnmatch.php).

PHP classes found in files excluded via the `$excludeFiles` parameter will not be accessible to the container via the `get()` method; consequently, such PHP classes cannot be injected.

You can register PHP classes from multiple directories by calling the `import()` method repeatedly, providing a unique value for the `$namespace` parameter each time:

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
 
$builder = (new DiContainerBuilder())
  ->import(namespace: 'App\Services\\', src: '/app/src/Services')
  ->import(namespace: 'App\Actions\\', src: '/app/src/Actions');
```

#### Example of declaring PHP classes from the file system { #example }

PHP classes:

::: code-group

<<< @/_code/src/Services/ReportBuilder.php

<<< @/_code/src/Interfaces/ReportsStorageInterface.php

<<< @/_code/src/Storages/ReportsStorage.php

<<< @/_code/src/Various/Qux.php

:::

It is necessary to register the PHP classes from the project directory `'/app/src'`. PHP classes located in the `'/app/src/Various/'` directory must be excluded from the container.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportBuilder;
use App\Various\Qux;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/report_parameters.php')//[!code highlight]
    ->import(// [!code ++]
        namespace: 'App\\',// [!code ++]
        src: '/app/src/',// [!code ++]
        excludeFiles: [// [!code ++]
            '/app/src/Various/*.php',// [!code ++]
        ]// [!code ++]
    )// [!code ++]
    ->build();

$reportBuilder = $container->get(ReportBuilder::class);

var_dump($reportBuilder->storage->dir);
var_dump($reportBuilder->lastDays);
var_dump($container->has(Qux::class));

$container->get(Qux::class); //[!code error]
```

<<< @/_code/config/report_parameters.php

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string (9) "/var/data"
int(10)
bool(false)
PHP Warning:  Uncaught Kaspi\DiContainer\Exception\NotFoundException: No entry was found for 'App\Various\Qux' identifier.
```

<!--@include: ../../_include/term_notes.md-->
