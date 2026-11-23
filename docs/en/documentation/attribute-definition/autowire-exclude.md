---
aside: false
title: AutowireExclude attribute
---
### AutowireExclude attribute

The `\Kaspi\DiContainer\Attributes\AutowireExclude` attribute excludes a PHP class or interface from the container's injectable dependencies. The FQCN [^FQCN] will be inaccessible to the container's `get()` and `has()` methods.

Attribute configuration:
- `\Attribute::TARGET_CLASS`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\AutowireExclude::__construct()
```

The attribute has no parameters.

> [!WARNING]
> The `\Kaspi\DiContainer\Attributes\AutowireExclude` attribute excludes any other attributes from the configuration process.

#### Example of a PHP class exception { #example }

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

final class Foo
{
    // …
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

:::

Exclude the PHP class `\App\Services\Foo` from the container using the `\Kaspi\DiContainer\Attributes\AutowireExclude` attribute.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\AutowireExclude;

#[AutowireExclude]// [!code ++]
final class Foo
{
    // …
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
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

var_dump($container->has(Foo::class));
var_dump($container->has(Bar::class));
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
(bool) false
(bool) true
```

<!--@include: ../_include/term_notes.md-->

