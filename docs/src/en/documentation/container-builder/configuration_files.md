---
outline: [2,4]
title: Definition configuration files
---
### Definition configuration files { #title }

You can use configuration files and [helper functions for declaring container definitions](../php-definition/index.md#helper-fn) to define container elements.

The `\Kaspi\DiContainer\DiContainerBuilder` class provides the [`load()`](methods/load-definitions-from-file.md#load) and [`loadOverride()`](methods/load-definitions-from-file.md#loadoverride) methods, which are designed for declaring container definitions and their corresponding identifiers[^ContainerId] based on configuration files.

The file **must use the `return` keyword** and return an [`iterable`](https://www.php.net/manual/en/language.types.iterable.php) or a `callable` type.

In PHP, `iterable` is a special pseudo-type that encompasses any arrays and objects of classes that implement the [`\Traversable` interface](https://www.php.net/manual/en/class.traversable.php).

In **configuration files**, it is recommended to use anonymous functions that act as [generators](https://www.php.net/manual/en/language.generators.overview.php) (functions utilizing the `yield` keyword); this allows the declaration of definitions to be deferred until the moment they are actually configured.

The container configuration tracks the uniqueness of container identifiers[^ContainerId]. When the `DiContainerBuilder::build()` method is called to construct the container, an exception—implementing the `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface` interface—will be thrown if any non-unique container definition identifiers are detected within the configuration files.

### Anonymous function and generator { #example-generator-simple }

A configuration file that returns an `iterable` type can be represented, for example, by an **anonymous function**. The result of executing such a function is a generator (`\Generator`) that yields PHP class declarations using the `yield` keyword.

```php
<?php
declare(strict_types=1);

// file: /app/config/as_generator_simple.php //[!code highlight]

use App\Services\Foo;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments('baz val');
};
```

> [!TIP]
> For the PHP class `App\Services\Foo`, the container identifier [^ContainerId] will be generated automatically via the [`diAutowire()` helper function](../php-definition/di-autowire.md).

### Anonymous function with a configurator and a generator { #example-generator-configurator }

A configuration file can be represented as a `callable`; in this case, the container passes a [container definition configurator](definitions-configurator/index.md) as the first argument.

An **anonymous function** can be used as this `callable`.

```php
<?php
declare(strict_types=1);

// file: /app/config/as_generator_with_configurator.php // [!code highlight]

use App\Services\Foo;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;//[!code ++]

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {//[!code --]
return static function (DefinitionsConfiguratorInterface $configurator): Generator {//[!code ++]
    $configurator->removeDefinition(Baz::class);// [!code highlight]
    $configurator->setParameter('params.baz', 'Lorem ipsum');// [!code highlight]

    yield diAutowire(Foo::class)
        ->bindArguments('baz val');
};
```

### Callable type with a configurator  { #example-callable-configurator }

The configuration file can be represented as a `callable` type that returns `void` when invoked.

```php
<?php
declare(strict_types=1);

// file: /app/config/callable_with_configurator.php // [!code highlight]

use App\Services\Foo;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {//[!code --]
return static function (DefinitionsConfiguratorInterface $configurator): void {//[!code ++]
    $configurator->removeDefinition(Baz::class);
    $configurator->setParameter('params.baz', 'Lorem ipsum');
    $configurator->setDefinition(
        Foo::class,
        diAutowire(Foo::class)
            ->bindArguments('baz val')
    );
};
```

### PHP array { #example-array }

The configuration file can be represented as a PHP array.

```php
<?php

// file: /app/config/as_array.php // [!code highlight]

use App\Services\Foo;

use function Kaspi\DiContainer\diAutowire;

return [
    diAutowire(Foo::class)
        ->bindArguments('baz val')
];
```

<!--@include: ../_include/term_notes.md-->
