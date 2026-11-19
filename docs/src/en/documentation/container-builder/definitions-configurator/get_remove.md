---
outline: [2,4]
title: Declaring and retrieving container definitions
---
### Declaring and retrieving container definitions { #title }

When building a container via `\Kaspi\DiContainer\DiContainerBuilder`, all declared definitions will be available within the definition configurator, including those imported via the [`DiContainerBuilder::import()` method](../index.md#dicontainerbuilder-import).
### getDefinitions()

The `getDefinitions()` method returns a collection of all definitions declared at the time the container is built, where the collection key is the container identifier[^ContainerId].

The method can only return definitions that were declared prior to the call to `getDefinitions()`.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::getDefinitions(): iterable
```

Example of use:

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {

    foreach ($configurator->getDefinitions() as $id => $definition) {// [!code highlight]
        // do something.
    }// [!code highlight]

};
```

### setDefinition()

The `setDefinition()` method declares a definition and its container identifier[^ContainerId]. If the identifiers match, this method will replace any previously declared container definition.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::setDefinition(
    string $id,
    mixed $definition
): void
```

Parameters:

- `$id` – the container identifier.
- `$definition` – the container definition.

Usage example:

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use App\Services\Mail;

use function Kaspi\DiContainer\{diAutowire, diParameter};
use function getenv;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    if ('STAGES' === getenv('APP_ENV')) {
        $configurator->setDefinition(// [!code ++]
            Mail::class,// [!code ++]
            diAutowire(Mail::class)// [!code ++]
                ->bindArguments(// [!code ++]
                    host: diParameter('mail.host.local'),// [!code ++]
                    port: diParameter('mail.port.local'),// [!code ++]
                );// [!code ++]
        );// [!code ++]
    }
};
```

### getDefinition()

The method retrieves the declared container definition using the container identifier[^ContainerId].

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::getDefinition(
    string $id,
    ?callable $fallback = null
): mixed
```

Parameters:
- `$id` – the identifier of the definition container.
- `$fallback` – a fallback handler for cases where the definition is not found.

> [!TIP]
> The `$fallback` parameter uses the type `callable(string $id): mixed`.
>
> If the `$fallback` parameter is set to `null` and the definition is not found, an exception implementing the `\Kaspi\DiContainer\Interfaces\Exceptions\NotFoundDefinitionInterface` interface will be thrown.


Example of use:

```php
<?php
declare(strict_types=1);

// file: /app/config/foo_bar_services.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\Exception\NotFoundDefinition;

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    // without specification $fallback
    try {
        $configurator->getDefinition('foo_bar');// [!code ++]
    } catch (NotFoundDefinition) {// [!code warning]
        $configurator->setDefinition(// [!code highlight]
            'foo_bar',// [!code highlight]
            diAutowire(FooBar::class)// [!code highlight]
                ->bindArguments('foo', 'bar')// [!code highlight]
        );// [!code highlight]
    }

    // Use $fallback
    $qux = $configurator->getDefinition('qux', static fn () => null);// [!code ++]

    if (null === $qux) {
        $configurator->setDefinition(// [!code highlight]
            'qux',// [!code highlight]
            diAutowire(Qux::class)// [!code highlight]
                ->setup('doConfig')// [!code highlight]
        );// [!code highlight]
    }
};
```

### findTaggedDefinitions()

The `findTaggedDefinitions()` method returns a collection of all declared definitions that carry a specific tag, where the collection key is the container identifier[^ContainerId].

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::findTaggedDefinitions(
    string $tag
): iterable
```

Parameters:
- `$tag` – tag name, or the FQCN[^FQCN] of an interface.

> [!TIP]
> In the `$tag` parameter, you can specify the [interface's FQCN](../../tags/interface-as-tag.md).

Example of use:

```php
<?php
declare(strict_types=1);

// file: /app/config/services_tagged.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;
use App\Interfaces\FooInterface;
use App\Services\Client;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $definitions = $configurator->findTaggedDefinitions(FooInterface::class);// [!code ++]
    
    foreach ($definitions as $id => $definition) {// [!code highlight]
        $definition->setup('setClient', diAutowire(Client::class));
    }// [!code highlight]
};
```

### removeDefinition()

The `removeDefinition()` method excludes an identifier[^ContainerId] from the container and removes the definition declaration, if one was previously made.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::removeDefinition(
    string $id,
): void
```

Parameters:

- `$id` – container identifier.

Any excluded container IDs will not be injectable into parameters.

When receiving through the container method `has()`, `false` will be returned, and when trying to get an excluded identifier through the container method `get()`, an exception implementing the interface `\Psr\Container\NotFoundExceptionInterface` will be thrown.

Usage example:

```php
<?php
declare(strict_types=1);

// file: /app/config/services_removed.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use App\Services\Qux;

use function getenv;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    if ('STAGES' === getenv('APP_ENV') {
        $configurator->removeDefinition(Qux::class);// [!code ++]
    }
};
```

<!--@include: ../../_include/term_notes.md-->
