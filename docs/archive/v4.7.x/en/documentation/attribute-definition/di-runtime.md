---
outline: [2,4]
title: DiRuntime attribute
---

### DiRuntime attribute

The `\Kaspi\DiContainer\Attributes\DiRuntime` attribute declares a PHP class within the container whose actual state will be initialized outside the container, with its value being set only at runtime.

In the context of a dependency container, such a PHP class is called a **“[Runtime Definition](../runtime-definition/index.md)”**.

Attribute configuration:
- `\Attribute::TARGET_CLASS`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\DiRuntime::__construct(
    string $containerIdentifier = '',
    ?string $message = null,
    array|\Kaspi\DiContainer\Attributes\Tag|null $tags = null,
    callable|false|string $resetter = false,
)
```
Parameters:
- `$containerIdentifierOrClass` – the container identifier[^ContainerId] for a "runtime definition".
- `$message` – the message to display if an error occurs.
- `$tags` – a tag declaration with metadata.
- `$resetter` – a [value used to reset an object's state](../object-resetters/index.md).

### Container identifier for Runtime Definition { #container-id }

When declaring a PHP class using the `\Kaspi\DiContainer\Attributes\DiRuntime` attribute, an empty string for the `$containerIdentifier` parameter is interpreted by the container as an identifier corresponding to the FQCN[^FQCN] of the PHP class being declared.

To declare the PHP class `\App\Services\FooService` via this attribute as a "Runtime Definition," the container identifier must be the string `'App\Services\FooServices'`.

::: code-group

```php [FooService.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/FooService.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;//[!code ++]

#[DiRuntime]//[!code ++]
final class FooService
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
use App\Services\FooService;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$container->has(FooService::class);
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
(bool) true
```

#### Runtime Definition declaration with multiple identifiers { #container-id-multuple }

The `\Kaspi\DiContainer\Attributes\DiRuntime` attribute can be used to register the same PHP class within the container under different identifiers[^ContainerId].

To register the PHP class `\App\Services\FooService` as a "Runtime Definition" via this attribute, the container identifier must be specified as the string `'App\Services\FooService'`.

To register the PHP class `\App\Services\FooService` as a "Runtime Definition" via this attribute, the container identifier must be specified as the string `'services.foo_service'`.

::: code-group

```php [FooService.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/FooService.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;//[!code ++]

#[DiRuntime]//[!code ++]
#[DiRuntime('services.foo_service')]//[!code ++]
final class FooService
{
    // …
}
```

:::

### Tags { #tags }

The `$tags` parameter of the `\Kaspi\DiContainer\Attributes\DiRuntime` attribute sets a tag and metadata for the PHP class being declared, using the [`\Kaspi\DiContainer\Attributes\Tag`](tag.md) attribute.

A value of `null` for the `$tags` parameter instructs the container to **set the tag based on the `Tag` attribute specified within the PHP class being declared**.

A value of `[]` (an empty array) for the `$tags` parameter instructs the container **not to assign any tag to the PHP class being declared**, even if a `Tag` attribute is present on that class.

::: code-group

```php [FooService.php]
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;

#[DiRuntime(
    tags: [//[!code highlight]
        new Tag('tags.fuze'),//[!code ++]
        new Tag('tags.runtime_service'),//[!code ++]
    ]//[!code highlight]
)]
final class FooService
{
    // …
}
```

:::

<!--@include: ../_include/term_notes.md-->
