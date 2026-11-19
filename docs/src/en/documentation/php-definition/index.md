---
aside: false
title: Declaration in configuration files
---
### Declaration in configuration files { #overview }

To instruct the container on how to inject dependencies into PHP classes or callable types, you use a mechanism for declaring definitions within [configuration files](../container-builder/configuration_files.md) or [configuration collections](../container-builder/methods/load-definitions-from-collection.md).

The [DiContainerBuilder class](../container-builder/index.md) is used to build the container.

Definitions are declared via helper functions that provide a simple and intuitive set of methods for various types of container definitions. These helper functions allow you to pass arguments, invoke setter methods, or add tags.

Since these helper functions feature lazy parameter initialization, they have a minimal impact on the initial loading of the container.

> [!TIP]
> ✅ You do not need to explicitly instruct the container on how to inject a type-hinted parameter [^TypeHints] for a PHP class method or a `callable`; provided that the required type has already been registered in the container or requires no additional configuration, the container will automatically inject the dependency.

> [!TIP]
> ✅ For parameters in PHP class methods or `callable` types, you can specify [scalar types](https://www.php.net/manual/en/language.types.type-system.php#language.types.type-system.atomic.scalar), `null`, or enumerated types directly – without needing to specify how such dependencies should be injected. For recurring scalar values, it is recommended to use [container parameters](../container-parameters/index.md).

#### Helper functions { #helper-fn }

  - [diAutowire()](di-autowire.md) – declares a PHP class in the container.
  - [diCallable()](di-callable.md) – dependency injection via a call to a `callable` type.
  - [diFactory()](di-factory.md) – dependency injection via the factory pattern.
  - [diGet()](di-get.md) – dependency injection by container identifier.
  - [diParameter()](di-parameter.md) – dependency injection from a container parameter.
  - [diParameterRuntime()](di-parameter-runtime.md) – dependency injection from a “runtime container parameter”.
  - [diProxyClosure()](di-proxy-closure.md) – inject a “lazy” dependency.
  - [diTaggedAs()](di-tagged-as.md) – incorporating a collection by tag.
  - [diValue()](di-value.md) – dependency injection “as is”.
  - [diRuntime()](di-runtime.md) – inject a “runtime definition”.

<!--@include: ../_include/term_notes.md-->