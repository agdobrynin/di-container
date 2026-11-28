---
outline: [2,4]
title: ProxyClosure attribute
---
### ProxyClosure attribute

The `\Kaspi\DiContainer\Attributes\ProxyClosure` attribute injects a "lazy" dependency via the built-in PHP class [`\Closure`](https://www.php.net/manual/en/class.closure.php), deferring the dependency's initialization until the moment it is actually used.

This approach to declaring dependencies is useful for injecting "heavy" dependencies that require a long initialization time or involve resource-intensive computations.

> [!IMPORTANT]
> Be sure to type-hint the parameter[^TypeHints] as `\Closure` or `callable` when performing dependency injection via the `\Kaspi\DiContainer\Attributes\ProxyClosure` attribute.
> ```php{2}
>  public function __construct(
>       private readonly \Closure $bar
>  ) {}
> ```

> [!TIP]
> If you are using PHP 8.4 or higher, it is preferable to inject dependencies using PHP’s built-in [“lazy objects”](../lazy-injection/index.md) mechanism, rather than using the `\Kaspi\DiContainer\Attributes\ProxyClosure` attribute.

Attribute configuration:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\ProxyClosure::__construct(
    string $containerIdentifier
)
```

Parameters:
- `$containerIdentifier` – container identifier [^ContainerId].

### Example of implementing a parameter with deferred initialization { #example }

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(
        private readonly bar $bar
    ) {}

    public function getBar(): Bar
    {
        return $this->bar;
    }
}
```

<<< @/_code/php-definition/di-proxy-closure/Bar.php

:::


The PHP class `\App\Services\Bar` performs resource-intensive calculations upon initialization. You need to inject `\App\Services\Bar` into the constructor of the class `\App\Services\Foo` by employing a "lazy" initialization pattern, utilizing the built-in PHP class `\Closure`.

::: code-group

```php {13-15} [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Closure;//[!code ++]
use Kaspi\DiContainer\Attributes\ProxyClosure;//[!code ++]

final class Foo
{
    /**
     * @param Closure(): Bar $barProxy
     */
    public function __construct(
        private readonly bar $bar//[!code --]
        #[ProxyClosure(Bar::class)]//[!code ++]
        private readonly Closure $barProxy//[!code ++]
    ) {}

    public function getBar(): Bar
    {
        return $this->bar;//[!code --]
        return ($this->barProxy)();//[!code ++]
    }
}
```

:::

::: code-group
```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->getBar() instanceof Bar);
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
