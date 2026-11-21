---
outline: [2, 4]
title: Tag priority
---
### Tag priority { #priority-by-tag }

The tag priority is specified in the `$priority` parameter of the `bindTag()`[^BindTag] method, or of the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute.

### Configuration example { #example-priority-by-tag }

PHP classes:

::: code-group

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

:::

You need to inject a collection of elements tagged with `'tags.ordered'` into the `$services` parameter via the constructor of the `\App\Services\Foo` class.

The injected collection must follow this specific order:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

Use the **tag priority** to define the order of elements within the collection.


#### Declaration in configuration files { #priority-by-tag-php-definition }

In the `tagged_services.php` [configuration file](../../container-builder/configuration_files.md), you need to declare the `'tags.ordered'` tag for PHP classes, specifying the priority for each tag via the `$priority` parameter of the `bindTag()` method.

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
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            priority: 10,//[!code ++]
        );

   yield diAutowire(Qux::class)
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            priority: 100,//[!code ++]
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
string(16) "App\Services\Baz"
string(16) "App\Services\Bar"
```

The sort order of the embedded collection is determined by the `$priority` values for each tag.

#### Declaration via attributes { #priority-by-tag-attributes }

Assign the `'tags.ordered'` tag to PHP classes using the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute, specifying a `$priority` parameter for each tag.

::: code-group

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php // [!code highlight]
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code highlight]

#[Tag(// [!code highlight]
    'tags.ordered',// [!code highlight]
    priority: 100,// [!code ++]
)]// [!code highlight]
final class Qux
{
    // …
}
```
<<< @/_code/Tags/src/Services/BazOrderedAttr.php [Baz.php]

<<< @/_code/Tags/src/Services/BarOrderedAttr.php [Bar.php]

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
string(16) "App\Services\Baz"
string(16) "App\Services\Bar"
```

The sort order of an injected collection is determined by the values  in the `$priority` parameter of the `\Kaspi\DiContainer\Attributes\Tag` attribute for each tag.

<!--@include: ../../php-definition/_include/bind_notes.md-->
