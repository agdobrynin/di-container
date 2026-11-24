---
outline: [2, 4]
title: Tag priority method
---
### Tag priority method { #priority-by-method }

For elements of an injected collection represented by a PHP class, the priority within the collection can be declared via a priority method.

When declaring a tag in [configuration files](../../container-builder/configuration_files.md), the priority method is specified via [metadata](../tag-metadata.md) within the `$options` parameter of the `bindTag()`[^BindTag] method. **The metadata key must be** the string `'priority.method'`, and the value must be the name of the method.

When declaring a tag using the `\Kaspi\DiContainer\Attributes\Tag` attribute, the priority method is specified via the `$priorityMethod` parameter.

<!--@include: _include/method_note.md-->

### Configuration example { #example }

PHP classes:

::: code-group

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

:::

It is necessary to inject a collection of elements tagged with `'tags.ordered'` into the `$services` constructor parameter of the PHP class `\App\Services\Foo`.

In this collection, the PHP class `\App\Services\Qux` must be the first element, while the order of the remaining elements may be arbitrary. To establish a priority for the `\App\Services\Qux` element within the collection, we will utilize the option to define a priority method for the collection item by implementing the `\App\Services\Qux::getPriority()` method:

<<< @/_code/Tags/src/Services/QuxPriorityMethod.php


#### Declaration in configuration files { #php-definition }

In the `tagged_services.php` configuration file, declare the `'tags.ordered'` tag for the PHP class `\App\Services\Qux` and specify the priority method via metadata in the `$options` parameter of the `bindTag()`[^BindTag] method; for all other classes, simply declare the `'tags.ordered'` tag using the `bindTag()` method.

**The metadata key for the "priority method" must be** the string `'priority.method'`, with the value set to `'getPriority'`.

::: code-group

<<< @/_code/Tags/container_build_ordered.php [container_build.php]

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php //[!code highlight]

use App\Services\{Bar, Baz, Qux};

use function \Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag('tags.ordered');//[!code highlight]
   
   yield diAutowire(Baz::class)
        ->bindTag(name: 'tags.ordered');//[!code highlight]

   yield diAutowire(Qux::class)
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            options: [//[!code ++]
               'priority.method' => 'getPriority',//[!code ++]
            ]//[!code ++]
        );
};
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\Foo;

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.ordered')//[!code highlight]
        );
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(16) "App\Services\Qux"
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
```

#### Declaration via attributes { #attributes }

Declare the `'tags.ordered'` tag for the PHP class `\App\Services\Qux`, specifying a priority method via the `$priorityMethod` parameter of the `\Kaspi\DiContainer\Attributes\Tag` attribute. For other PHP classes, declare the `'tags.ordered'` tag using the `\Kaspi\DiContainer\Attributes\Tag` attribute.

::: code-group

<<< @/_code/Tags/src/Services/QuxPriorityMethodAttr.php [Qux.php]

<<< @/_code/Tags/src/Services/BarOrderedAttr.php [Bar.php]

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;//[!code highlight]

#[Tag('tags.ordered')]//[!code highlight]
final class Baz
{
    // …
}
```

<<< @/_code/Tags/src/Services/FooOrderedAttr.php [Foo.php]

:::

::: code-group

<<< @/_code/Tags/container_build_ordered_attr.php [container_build.php]

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(16) "App\Services\Qux"
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
```

<!--@include: ../../php-definition/_include/bind_notes.md-->
