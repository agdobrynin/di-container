---
outline: [3, 4]
title: Dependency injection into a union-typed parameter
---
### Dependency injection into a union-typed parameter { #overview }

When performing injection for a parameter with a [union type](https://www.php.net/manual/en/language.types.type-system.php#language.types.type-system.composite.union), the container configuration is used to search for available PHP class declarations. If multiple logically equivalent options for dependency injection are found, an exception is thrown, indicating that the type of the argument being injected must be specified more precisely.

Below, we will look at an example of how to define an injection for a parameter with a union type.

### PHP classes { #php-classes }

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

final class Foo
{ 
    public function __construct(
        public readonly Bar|Baz $dependency
    ) {}

}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php [!code highlight]

namespace App\Services;

final class Baz
{
    // …
}
```

:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->build();

$foo = $container->get(Foo::class);//[!code error]

var_dump($foo->dependency); 
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

An exception will be thrown:

> [!DANGER] PHP Fatal error:
> Uncaught Kaspi\DiContainer\Exception\AutowireParameterTypeException:
> Cannot automatically resolve dependency in App\Services\Foo::__construct().
> Please specify the Parameter #0 [ \<required\> App\Services\Bar | App\Services\Baz $dependency ].

To resolve the error, you need to specify the argument type for the `$dependency` constructor parameter in the PHP class `\App\Services\Foo`.

#### Declaration in configuration files { #php-definitions }

In the `services.php` configuration file, using the [`diGet()`](../documentation/php-definition/di-get.md) helper function, we specify which dependency needs to be implemented in the `$dependency` constructor parameter of the `\App\Services\Foo` PHP class.

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->dependency); 
```
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Service\{Foo, Baz};

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(//[!code ++]
            dependency: diGet(Baz::class)//[!code ++]
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
  object(App\Services\Baz)#2 (0) {
  }
```

#### Declaration via an attribute { #php-attributes }

Using the [`\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md) attribute, we specify exactly which dependency should be injected into the `$dependency` constructor parameter of the PHP class `\App\Services\Foo`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Inject;//[!code ++]

final class Foo
{ 
    public function __construct(
        #[Inject(Baz::class)]//[!code ++]
        public readonly Bar|Baz $dependency
    ) {}

}
```
:::


::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->dependency); 
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
  object(App\Services\Baz)#2 (0) {
  }
```
