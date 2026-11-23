---
aside: false
title: Container reset
---
### Container reset { #title }

Performing a reset on the dependency container will return it to its initial state — clearing its internal buffer of properties and dependencies, bringing them back to the same state they were in when the container was first initialized.

### Complete reset of the dependency container { #full-reset }

The `reset()` method, which performs a reset of the container, can be called on both a runtime container and a [compiled container](../container-builder/methods/compile-to-file.md).

Resetting the container restores its entire configuration to the state it was in at the time of its initial initialization. For all PHP classes and container definitions declared [via helper functions](../php-definition/index.md) or [attributes](../attribute-definition/index.md), any internal property buffers and auxiliary caches will also be cleared.

[Container parameters](../container-parameters/index.md) are likewise reset.

> [!WARNING]
> For [runtime objects](../runtime-definition/index.md) and [container runtime parameters](../container-parameters/inject_runtime_params.md) defined within the container, internal buffers and caches will also be cleared resetting them to their initial state. If these values are still required after the container has been reset, you will need to re-declare them.

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
                ->import('App\\', '/app/src')
                ->load('/app/config/container/services.php')
                ->loadParameters('/app/config/container/parameters.php')
                ->build();

// ...

$container->reset();//[!code ++]
```
