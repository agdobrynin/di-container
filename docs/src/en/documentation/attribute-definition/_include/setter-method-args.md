The variable-length parameter `$argument` passes arguments to the setter method.

When declaring dependency injection for a specific parameter of a setter method, you can use [named arguments](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments).

Setter method parameters that do not receive arguments will be injected by the container automatically, based on the configuration.

<!--@include: to_arguments.md-->

An example of using setter methods for dependency injection can be found in the [Cookbook](../../../cookbook/autowire-setup.md#php-attributes) section.