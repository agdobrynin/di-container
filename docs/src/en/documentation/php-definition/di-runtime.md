---
outline: [2,4]
title: Helper function diRuntime()
---
### Helper function diRuntime()

The helper function `diRuntime()` declares a PHP class within the container; its actual state is initialized outside the container, and its value is set only at runtime.

In the context of a dependency injection container, such a PHP class is called a **“[Runtime Definition](../runtime-definition/index.md)”**.

> [!WARNING]
> A helper function cannot be applied to the parameters of a method or function. To inject dependencies into the parameters of methods or callable types, use the [helper function `diGet()`](di-get.md).

Function signature:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\{
    DiDefinitionTagArgumentInterface as Tag,
    DiDefinitionResetterSetterInterface as Resetter
};

\Kaspi\DiContainer\diRuntime(
    string $containerIdentifierOrClass,
    ?string $message = null,
    ?string $classDefinition = null
): Tag & Resetter
```

Parameters:

- `$containerIdentifierOrClass` – the container identifier[^ContainerId] for a "runtime definition".
- `$message` – the error message.
- `$classDefinition` – a fallback FQCN[^FQCN] declaration for the object.

The `diRuntime()` function returns an object that provides the following methods:

- `bindTag()` – add a tag with metadata.
- `setResetter()` – set a value used to reset the object's state.

> [!TIP]
> Read the documentation section "[Configuring an object in the runtime container](../runtime-definition/index.md)".

### bindTag()

Tags allow a "runtime definition" to be associated with a collection of elements marked with the same tag.

<!--@include: ./_include/bind_tag.md-->

### setResetter()

<!--@include: _include/set-resetter.md-->

### Container identifier for Runtime Definition { #container-id }

The `diRuntime()` helper function can pass an identifier [^ContainerId] to the container via the `$containerIdentifierOrClass` parameter, making it accessible through the container's `get()` and `has()` methods.

```php
<?php
declare(strict_types=1);

// file: /app/config/runtime_definitions.php // [!code highlight]

use App\Core\Kernel;

use function Kaspi\DiContainer\diRuntime;

return static function (): Generator {
    yield Kernel::class => diRuntime(Kernel::class);//[!code --]
    yield diRuntime(Kernel::class);//[!code ++]
};
```

> [!NOTE]
> The configuration file example above allows you to retrieve an object using the container method `$container->get(App\Core\Kernel::class)`.


It is also possible to declare an object with an arbitrary string-based container identifier[^ContainerId]:

```php
<?php
declare(strict_types=1);

// file: /app/config/runtime_definitions.php // [!code highlight]

use App\Core\Kernel;

use function Kaspi\DiContainer\diRuntime;

return static function (): Generator {
    yield 'services.core_kernel' => diRuntime(Kernel::class);
};
```

> [!NOTE]
> The configuration file example above allows you to retrieve an object using the container method `$container->get('services.core_kernel')`.

<!--@include: ../_include/term_notes.md-->