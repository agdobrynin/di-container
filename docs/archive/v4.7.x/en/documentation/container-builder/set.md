---
outline: [2,4]
title: Dynamic declaration of an object within a container
---
### Dynamic declaration of an object within a container { #title }

In some container usage scenarios, it is not possible to declare a PHP class or other definition during the container build process—using the [methods of the `DiContainerBuilder` class](index.md#methods) — because the configuration is not known in advance. The actual state of an object is initialized outside the container and is injected into the container only at runtime.

The container's `set()` method is defined by the `\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface` interface.

The method signature is as follows:

```php
\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface::set(
    string $id,
    mixed $definition
): static;
```

Parameters:
- `$id` – container identifier [^ContainerId].
- `$definition` – object.

> [!WARNING]
> The `set()` method may throw an exception that implements the `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierAlreadyRegisteredExceptionInterface` interface if the container identifier specified in the `$id` parameter is not unique within the container configuration.

> [!IMPORTANT]
> Dynamically declared objects will not be [compiled](methods/compile-to-file.md).

**To maintain high container performance, it is recommended to declare PHP classes using the [`diRuntime()` helper function](../php-definition/di-runtime.md) or the [`\Kaspi\DiContainer\Attributes\DiRuntime` attribute](../attribute-definition/di-runtime.md).**

### Example of dynamic object declaration within a container { #example }

```php
<?php
declare(strict_types=1);

// file: /app/src/container_builder.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use VendorFoo\Services\Bar;//[!code ++]

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

$object = new \VendorFoo\Services\Bar();

$container->set($object::class, $object);//[!code ++]

var_dump($container->get(Bar::class) instanceof Bar);
// bool(true)
```

<!--@include: ../_include/term_notes.md-->