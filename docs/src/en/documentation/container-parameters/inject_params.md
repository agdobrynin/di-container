---
outline: [2, 4]
title: Declaring dependency injection via container parameters
---
### Declaring dependency injection via container parameters { #title }

Injecting container parameters into the arguments of a method or a callable type[^CallableType]:
- via the helper function [`diParameter()`](../php-definition/di-parameter.md) in [configuration files](../container-builder/configuration_files.md).
- via the attribute [`\Kaspi\DiContainer\Attributes\Parameter`](../attribute-definition/parameter.md).

Both the helper function and the attribute accept a single argument, `$name`, which represents the name of the container parameter.

> [!TIP]
> <!--@include: _include/param_name_on_empty_arg.md-->

### Example { #example }

PHP classes:

::: code-group
<<< @/_code/src/Services/Foo.php
<<< @/_code/src/Services/Bar.php
<<< @/_code/src/Enums/SetupEnum.php
:::

Container parameter configuration files:

::: code-group
<<< @/_code/config/params.php
<<< @/_code/config/params_dev.php
<<< @/_code/config/params_notify.php
:::

> [!IMPORTANT]
> <!--@include: _include/declare_order.md-->

Inject values into the constructor of the PHP class `\App\Services\Foo` via container parameters.

The container parameters must be defined according to the following condition: if the `APP_ENV` environment variable is set to `'dev'`, the container parameters should be loaded from the `params_dev.php` file; in all other cases, they should be loaded from the `params.php` file.

#### Declaration in configuration files { #helper-fn-di-parameter }

In the `services.php` configuration file, define the PHP class `\App\Services\Foo` and configure the injection of container parameters into its constructor using the `diParameter()` helper function:
- `'setupType'` into the `$setupType` constructor parameter.
- `'emails.notify'` into the `$mailTo` constructor parameter.
- `'app.debug'` into the `$debug` constructor parameter.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

use function {getenv, putenv};

putenv('APP_ENV=dev');//[!code ++]

$builder = (new DiContainerBuilder())
    ->loadParameters('/app/config/params.php')//[!code ++]
    ->loadParameters('/app/config/params_notify.php')//[!code ++]
    ->load('/app/config/services.php');// [!code highlight]

if ('dev' === getenv('APP_ENV')) {//[!code ++]
    $builder->loadParameters('/app/config/params_dev.php');//[!code ++]
}//[!code ++]

$container = $builder->build();

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

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(//[!code ++]
            /*
             * Parameter #2 of the constructor, named `$setupType`.
             * Equivalent to the declaration diParameter('setupType')
             */
            setupType: diParameter(),//[!code ++]
            mailTo: diParameter('emails.notify'),//[!code ++]
            debug: diParameter('app.debug'),//[!code ++]
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
object(App\Services\Bar)#2 (0) {
}
enum(App\Enums\SetupEnum::LOCAL)
string(33) "admin@localhost, manager@localhost"
bool(true)
```

#### Declaration via an attribute { #attribute-parameter }

Declare the injection of container parameters via the `\Kaspi\DiContainer\Attributes\Parameter` attribute into the constructor of the PHP class `\App\Services\Foo`.

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Enums\SetupEnum;
use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]

final class Foo
{
    public function __construct(
        public readonly Bar $bar,
        // Equivalent to the declaration #[Parameter('setupType')]//[!code warning]
        #[Parameter]//[!code ++]
        public readonly SetupEnum $setupType,
        #[Parameter('emails.notify')]//[!code ++]
        public readonly string $mailTo,
        #[Parameter('app.debug')]//[!code ++]
        public readonly bool $debug,
    ) {}
}
```
::: 

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

use function {getenv, putenv};//[!code ++]

putenv('APP_ENV=dev');

$builder = (new DiContainerBuilder())
    ->loadParameters('/app/config/params.php')//[!code ++]
    ->loadParameters('/app/config/params_notify.php');//[!code ++]

if ('dev' === getenv('APP_ENV')) {//[!code ++]
    $builder->loadParameters('/app/config/params_dev.php');//[!code ++]
}//[!code ++]

$container = $builder->build();

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
string(33) "admin@localhost, manager@localhost"
bool(true)
```

<!--@include: ../_include/term_notes.md-->