---
aside: false
title: Declaration via attributes
---
### Declaration via attributes { #title }

To instruct the container on how to inject dependencies into PHP classes or callable types[^CallableType], a mechanism for declaring definitions via attributes is used.

In the [container configuration](../container-config/index.md#use-attribute), the `$useAttribute` parameter is enabled by default.

To create a configured container, the builder class [DiContainerBuilder](../container-builder/index.md) is used.

Attributes contain metadata for declaring container definitions: arguments, injection via setter methods, or the declaration of tags.

**When declaring container definitions, you can combine attributes and [configuration files](../php-definition/index.md).**

> [!WARNING]
> Defining container entries or specifying dependency injections for method parameters via attributes takes precedence over definitions made in configuration files.

> [!IMPORTANT]
> To declare a PHP class or interface, you must choose only one declaration method: either via attributes or through configuration files.

### Available attributes { #attributes }
- [Autowire](autowire.md) – declaring a PHP class in a container.
- [AutowireExclude](autowire-exclude.md) – excludes from the container's injected dependencies.
- [DiFactory](di-factory.md) – dependency injection via the factory pattern.
- [Inject](inject.md) – dependency injection by container identifier[^ContainerId].
- [InjectByCallable](inject-by-callable.md) – dependency injection via a call to a `callable` type.
- [Parameter](parameter.md) – dependency injection from "container parameters".
- [ParameterRuntime](parameter-runtime.md) – dependency injection from "runtime container parameters".
- [ProxyClosure](proxy-closure.md) – implementation of "lazy" dependency.
- [Service](service.md) – specifies the implementation of a PHP interface.
- [Setup](setup.md) – dependency injection into a PHP class via a mutable setter method.
- [SetupImmutable](setup-immutable.md) – Dependency injection into a PHP class via an immutable setter method.
- [SetupPriority](setup-priority.md) – setter method priority.
- [Tag](tag.md) – tag declaration.
- [TaggedAs](tagged-as.md) – a collection by tag.
- [DiRuntime](di-runtime.md) – implement "runtime definition".

<!--@include: ../_include/term_notes.md-->
