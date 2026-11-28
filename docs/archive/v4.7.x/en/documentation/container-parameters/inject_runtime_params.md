---
outline: [2, 4]
title: Runtime container parameters
---
### Runtime container parameters { #title }

In certain container usage scenarios, it may be necessary to declare a container parameter without knowing its value in advance that is, only the name of the parameter is known at the time of configuration.

The actual value of such a parameter is calculated outside the container and is assigned to it only at runtime, using the pre-defined parameter name.

These types of container parameters are referred to as **runtime container parameters**.

During [container compilation](../container-builder/methods/compile-to-file.md), declared dependency injections must be aware of the existence of **runtime container parameters**, the values of which will be set in the container only when the container itself is executed.

### Injecting a runtime container parameter { #inject }

- via the helper function [`diParameterRuntime()`](../php-definition/di-parameter-runtime.md) in [configuration files](../container-builder/configuration_files.md).
- via the attribute [`\Kaspi\DiContainer\Attributes\ParameterRuntime`](../attribute-definition/parameter-runtime.md).

Both the helper function and the attribute accept a single argument, `$name`, which represents the name of the container parameter.

> [!TIP]
> <!--@include: _include/param_name_on_empty_arg.md-->

To set the actual value of a **runtime container parameter**, you must use the `set()` method of an object that implements the `\Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface` interface; this object can be retrieved via the container's `parameters()` method.
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->build();

// …

// calculate the value of the parameter
$calculatedValue = doCalculateValue();//[!code ++]
// we set the actual value of the container parameter
$container->parameters()//[!code ++]
    ->set(//[!code ++]
        'params.runtime_foo',//[!code ++]
        $calculatedValue//[!code ++]
    );//[!code ++]
// …
```

### Example of inject a runtime container parameter { #example }

PHP classes:

::: code-group
<<< @/_code/src/Services/Foo.php
<<< @/_code/src/Services/Bar.php
<<< @/_code/src/Enums/SetupEnum.php
:::

Container parameter configuration files:


::: code-group
<<< @/_code/config/params_main.php
<<< @/_code/config/params_notify.php
:::

For the `$mailTo` and `$debug` constructor parameters of the PHP class `\App\Services\Foo`, configure injection using [container parameters](inject_params.md). Since the actual value for the `$setupType` parameter can only be determined outside the container, you must use a **runtime container parameter** to configure its injection.

#### Declaration in configuration files { #helper-fn-di-parameter-runtime }

In the `services.php` configuration file, register the PHP class `\App\Services\Foo`. Configure dependency injection for the constructor parameter `$setupType` using the helper function [`diParameterRuntime()`](../php-definition/di-parameter-runtime.md), and for the constructor parameters `$mailTo` and `$debug`—whose values are known in advance—use the helper function [`diParameter()`](../php-definition/di-parameter.md).

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Enums\SetupEnum;


$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/params_main.php')//[!code highlight]
    ->loadParameters('/app/config/params_notify.php')//[!code highlight]
    ->load('/app/config/services.php')// [!code highlight]
    ->build();


// …

$mockSetupTypeValue = static fn () => SetupEnum::LOCAL;// [!code highlight]

$calculatedSetupTypeValue = ($mockSetupTypeValue)();// [!code ++]

$container->parameters()// [!code ++]
    ->set(// [!code ++]
        'params.setup_type',// [!code ++]
        $calculatedSetupTypeValue,// [!code ++]
    );// [!code ++]

// …

$foo = $container->get(Foo::class);

var_dump($foo->bar);
var_dump($foo->setupType);
var_dump($foo->mailTo);
var_dump($foo->debug);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php  // [!code highlight]

use App\Services\Foo;

use function Kaspi\DiContainer\{diAutowire, diParameterRuntime, diParameter};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(//[!code highlight]
            setupType: diParameterRuntime('params.setup_type'),//[!code ++]
            mailTo: diParameter('emails.notify'),//[!code highlight]
            debug: diParameter('app.debug'),//[!code highlight]
        );//[!code highlight]
};
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
object(App\Services\Bar)#2 (0) {
}
enum(App\Enums\SetupEnum::LOCAL)
string(37) "admin@example.com, manager@example.com"
bool(false)
```

#### Declaration via an attribute { #attribute-parameter-runtime }

Specify the injection for the constructor parameter `$setupType` using the [`\Kaspi\DiContainer\Attributes\ParameterRuntime`](../attribute-definition/parameter-runtime.md) attribute within the PHP class `\App\Services\Foo`, and use the [`\Kaspi\DiContainer\Attributes\Parameter`](../attribute-definition/parameter.md) attribute for the remaining constructor parameters.

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use App\Enums\SetupEnum;
use Kaspi\DiContainer\Attributes\Parameter;// [!code highlight]
use Kaspi\DiContainer\Attributes\ParameterRuntime;// [!code ++]

final class Foo
{
    public function __construct(
        public readonly Bar $bar,
        #[ParameterRuntime('params.setup_type')]// [!code ++]
        public readonly SetupEnum $setupType,
        #[Parameter('emails.notify')]// [!code highlight]
        public readonly string $mailTo,
        #[Parameter('app.debug')]// [!code highlight]
        public readonly bool $debug,
    ) {}
}
```
::: 

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Enums\SetupEnum;


$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/params_main.php')//[!code highlight]
    ->loadParameters('/app/config/params_notify.php')//[!code highlight]
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();


// …

$mockSetupTypeValue = static fn () => SetupEnum::LOCAL;// [!code highlight]

$calculatedSetupTypeValue = ($mockSetupTypeValue)();// [!code ++]

$container->parameters()// [!code ++]
    ->set(// [!code ++]
        'params.setup_type',// [!code ++]
        $calculatedSetupTypeValue,// [!code ++]
    );// [!code ++]

// …

$foo = $container->get(Foo::class);

var_dump($foo->bar);
var_dump($foo->setupType);
var_dump($foo->mailTo);
var_dump($foo->debug);
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
object(App\Services\Bar)#2 (0) {
}
enum(App\Enums\SetupEnum::LOCAL)
string(37) "admin@example.com, manager@example.com"
bool(false)
```
