---
outline: [2,4]
title: Helper function diProxyClosure()
---
### Helper function diProxyClosure()

The helper function `diProxyClosure()` injects a lazy dependency via the built-in PHP class `\Closure`, deferring the dependency's initialization until the moment it is actually used.

This approach to declaring dependencies is useful for incorporating heavy dependencies that require a long initialization time or resource-intensive computations.

> [!IMPORTANT]
> Be sure to type-hint the parameter[^TypeHints] as `\Closure` or `callable` for dependency injection via the `diProxyClosure()` helper function.
> ```php{2}
>  public function __construct(
>       private readonly \Closure $bar
>  ) {}
> ```

> [!TIP]
> If you are using PHP 8.4 or higher, it is preferable to inject dependencies using PHP’s built-in [“lazy objects”](../lazy-injection/index.md) mechanism, rather than using the `diProxyClosure()` helper function.

Function signature:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;

\Kaspi\DiContainer\diProxyClosure(
    string $containerIdentifier,
    ?bool $isSingleton = null,
): DiDefinitionTagArgumentInterface
```

Parameters:
- `$containerIdentifier` – container identifier [^ContainerId].
- `$isSingleton` – The container will return the same object instance (singleton pattern). If set to `null`, the value will be determined based on the [container configuration](../container-config/index.md#is-singleton-service-default).

The `diProxyClosure()` function returns an object that provides the following methods:
- `bindTag()` – adds a tag containing metadata for identification.

### bindTag()

Tags allow a lazy definition to be associated with a collection of elements marked with a single tag.

<!--@include: ./_include/bind_tag.md-->

### Container identifier for lazy definition { #container-id }

To make a "lazy" definition accessible via the container's `get()` and `has()` methods, you must specify a container identifier[^ContainerId] in the configuration file that corresponds to the declared definition.

A resource-intensive PHP class that requires a lengthy initialization process:

<<< @/_code/php-definition/di-proxy-closure/Bar.php

The container method `$container->get('services.bar_lazy')` should return a "lazy" object of the class `\App\Services\Bar`.

::: code-group


```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Bar;

$container = (new DiContainerBuilder())
    ->load('/app/config/lazy_entries.php')//[!code highlight]
    ->build();

$barLazy = $container->get('services.bar_lazy');

var_dump($barLazy instanceof Closure);
var_dump(($barLazy)() instanceof Bar);
```

```php [lazy_entries.php]
<?php
declare(strict_types=1);

// file: /app/config/lazy_entries.php // [!code highlight]

use App\Services\Bar;

use function Kaspi\DiContainer\diProxyClosure;

return static function(): Generator {
    yield 'services.bar_lazy' => diProxyClosure(// [!code ++]
        Bar::class,// [!code ++]
        isSingleton: true// [!code ++]
    );// [!code ++]
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
bool(true)
bool(true)
```

### Example of implementing a parameter with deferred initialization { #example }

PHP classes:

::: code-group

<<< @/_code/php-definition/di-proxy-closure/Bar.php

<<< @/_code/php-definition/di-proxy-closure/Foo.php

:::

The PHP class `\App\Services\Bar` performs resource-intensive calculations upon initialization; it should be injected into the `$barProxy` parameter of the `\App\Services\Foo` class constructor using a "lazy" object initialization technique, implemented via PHP's built-in `\Closure` class.

::: code-group 

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->getBar() instanceof Bar);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diProxyClosure};

return static function(): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(// [!code ++]
            barProxy: diProxyClosure(Bar::class)// [!code ++]
        );// [!code ++]
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
bool(true)
```

> [!TIP]
> Use [PhpDocBlock](https://docs.phpdoc.org/guide/getting-started/what-is-a-docblock.html) for hints and autocompletion in your IDE:
> ```php{1-3}
>   /**
>    * @param Closure(): Bar $barProxy
>    */
>  public function __construct(private readonly Closure $barProxy) {}
> ```

<!--@include: ../_include/term_notes.md-->
