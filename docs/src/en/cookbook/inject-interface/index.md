---
outline: [2, 4]
title: Dependency injection into a parameter with an interface type
---
### Dependency injection into a parameter with an interface type { #overview }

In certain container usage scenarios, it is necessary to be able to declare a dependency injection within a method parameter or a callable type[^CallableType] that expects an object implementing a specific interface.

Such an object can be injected by associating the interface's FQCN[^FQCN] with a specific PHP class, allowing the container to automatically inject the required object.

The container offers various ways to implement such a declaration, depending on the specific task at hand.

> [!IMPORTANT]
> The PHP class object retrieved from the dependency injection container must implement the interface expected by the parameter.

---

### Globally register { #global }

You can globally register a specific PHP class as the implementation of an interface within the container; the container will then automatically inject this implementation whenever an interface is required, unless a different implementation has been explicitly specified for a particular method parameter or type being invoked.

To globally register an interface implementation, you must specify the interface name as the container identifier[^ContainerId] and provide its implementation – either [via a helper function](../../documentation/php-definition/index.md#helper-fn) or **by using the [`\Kaspi\DiContainer\Attributes\Service`](../../documentation/attribute-definition/service.md) attribute**.

💡 [Examples of global interface implementation registration](global.md).

---

### Declaration of an insertion into a specific parameter { #param }

For a method parameter or a type being instantiated that is represented by a specific interface, you can declare a dependency injection that differs from the global implementation defined for that interface.

You can declare dependency injection for such a parameter using a [helper function](../../documentation/php-definition/index.md#helper-fn) or [attributes](../../documentation/attribute-definition/index.md#attributes).

💡 [Examples of declaring dependency injection for a specific parameter](arg.md).

<!--@include: ../../documentation/_include/term_notes.md-->