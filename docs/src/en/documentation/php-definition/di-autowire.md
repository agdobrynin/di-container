---
outline: [2,5]
title: Helper function diAutowire()
---
### Helper function diAutowire()

The helper function `diAutowire()` registers a PHP class in the container.

Function signature:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\{
    DiDefinitionArgumentsInterface as Args,
    DiDefinitionSetupAutowireInterface as Setup,
    DiDefinitionTagArgumentInterface as Tag,
    DiDefinitionResetterSetterInterface as Resetter
};

\Kaspi\DiContainer\diAutowire(
    string $definition,
    ?bool $isSingleton = null,
    bool $isLazy = false,
): Args & Setup & Tag & Resetter
```
Parameters:
- `$definition` – The FQCN [^FQCN] of the PHP class being declared.
- `$isSingleton` – The container will return the same object instance (singleton pattern). If set to `null`, the value will be determined based on the [container configuration](../container-config/index.md#is-singleton-service-default).
- `$isLazy` – declare a PHP class as lazy object.

> [!TIP]
> Review the following sections of the documentation:
> - [Lazy injection of container objects](../lazy-injection/index.md).
> - [Resetting object state](../object-resetters/index.md).

The `diAutowire()` function returns an object that provides the following methods:
- `bindArguments()` – passes arguments to a PHP class constructor.
- `setup()` – declares dependency injection via a mutable setter method [^SetterMethodMutable].
- `setupImmutable()` – declares dependency injection via an immutable setter method [^SetterMethodImmutable].
- `bindTag()` – assigns a tag with metadata.
- `setResetter()` – specifies a value (or callback) used to reset the object's state.

### Container identifier for diAutowire() { #container-id }

The helper function `diAutowire()` can pass an identifier [^ContainerId] to the container from the `$definition` parameter, making it accessible via the container's `get()` and `has()` methods.

A [configuration file](../container-builder/configuration_files.md) with an automatically generated container identifier:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    yield Foo::class => diAutowire(Foo::class)//[!code --]
    yield diAutowire(Foo::class)//[!code ++]
};
```

The container identifier for a PHP class being declared can be specified as an arbitrary string.

```php
<?php
declare(strict_types=1);

use App\Services\Foo;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    /**
     * $container->get('services.foo_one')// [!code highlight]
     */
    yield diAutowire(Foo::class);//[!code --]
    yield 'services.foo_one' => diAutowire(Foo::class);//[!code ++]

    /**
     * $container->get('services.foo_two')// [!code highlight]
     */
    yield 'services.foo_two' => diAutowire(Foo::class);//[!code ++]
};
```

### bindArguments()

Arguments for a PHP class constructor.

<!--@include: _include/bind_arguments.md-->

#### Example of dependency injection via the bindArguments() method { #example-bind-arguments }

PHP classes:

::: code-group

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

You need to use the `bindArguments()` method to specify how the constructor should inject a dependency into a particular parameter:
- into the `$lastDays` parameter of the `\App\Services\ReportMaker` class;
- into the `$dir` parameter of the `\App\Storages\ReportStorage` class.

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

$report = $container->get(ReportMaker::class);

var_dump($report->lastDays);
var_dump($report->storage->dir);
```

<<< @/_code/config/report_services.php

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
int(10)
string(9) "/var/data"
```

### Dependency injection via setter methods { #di-autowire-setter-method }

In addition to dependency injection via a PHP class constructor, it is also possible to inject dependencies using setter methods.

#### setup()

The `setup()` method injects a dependency into a PHP class via a **mutable setter method** [^SetterMethodMutable] and is defined by the `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface` interface.

Method signature:

```php 
DiDefinitionSetupAutowireInterface::setup(
    string $method,
    array $arguments = []
)
``` 
Parameters:
- `$method` – the name of a setter method within the class.
- `$arguments` – the arguments for the setter method's parameters.

<!--@include: _include/setter_method_arg.md-->

##### Example of implementation via the setup() method { #example-setter-method-setup }

PHP class:

```php
<?php
declare(strict_types=1);

// file: /app/src/Storages/ReportStorage.php//[!code highlight]

namespace App\Storages;

final class ReportStorage
{
    private int $permission = 0777;

    public function __construct(
        public readonly string $dir
    ) {}

    public function setPermission(int $permission): void
    {
        $this->permission = $permission;
    }

    public function getPermission(): int
    {
        return $this->permission;
    }
}
```

Dependency injection must be performed via the setter method `\App\Storages\ReportsStorage::setPermission()`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Storages\ReportsStorage;

$container = (new DiContainerBuilder())
    ->load('/app/config/storage_service.php')//[!code ++]
    ->build();

$storage = $container->get(ReportsStorage::class);

var_dump($storage->getPermission());
```

```php [storage_service.php]
<?php
declare(strict_types=1);

// file: /app/config/storage_service.php//[!code highlight]

use App\Storages\ReportsStorage;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(ReportsStorage::class)
        ->bindArguments(
            dir: '/var/data'
        )
        ->setup(//[!code ++]
            'setPermission',//[!code ++]
            [0600]//[!code ++]
        );//[!code ++]
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
int(384)
```

> [!NOTE]
> [Example of using the `setup()` method](../../cookbook/autowire-setup.md#diautowire-setup)

#### setupImmutable()

The `setupImmutable()` method injects a dependency into a PHP class via an **immutable setter method**[^SetterMethodImmutable] and is defined by the `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface` interface.

The setter method must return `self`, `static`, or the type of the PHP class being declared within the `diAutowire()` helper function.

Method signature:

```php 
DiDefinitionSetupAutowireInterface::setupImmutable(
    string $method,
    array $arguments = []
)
``` 
Parameters:
- `$method` – the name of a setter method within the class.
- `$arguments` – the arguments for the setter method's parameters.

<!--@include: _include/setter_method_arg.md-->

> [!NOTE]
> [Example of using the `setupImmutable()` method](../../cookbook/autowire-setup.md)

### bindTag()

Tags allow a PHP class being declared to be associated with a collection of elements marked with the same tag.

<!--@include: _include/bind_tag.md-->

### setResetter()

<!--@include: _include/set-resetter.md-->

<!--@include: ../_include/term_notes.md-->
