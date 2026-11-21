---
outline: [2, 4]
title: Default priority method
---
### Default priority method { #priority-by-method-default }

In cases where the priority of a collection element cannot be determined from a tag, a **default priority method** is used.

If no **default priority method** is defined in the PHP class, the collection element will be assigned a priority value of `null`.

The **default priority method** is specified via the `$priorityDefaultMethod` parameter in the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function or within the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute.

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

In this collection, the PHP class `\App\Services\Qux` must be the first element, while the order of the remaining elements may be arbitrary. To ensure that the `\App\Services\Qux` element takes precedence, we will utilize the option to define a **default priority method** by implementing the `\App\Services\Qux::getPriority()` method:

<<< @/_code/Tags/src/Services/QuxPriorityMethod.php

#### Declaration in configuration files { #php-definition }

In the `services.php` configuration file, declare the injection of a collection of elements marked with the `'tags.ordered'` tag into the `$services` parameter of the `\App\Services\Foo` PHP class constructor, and specify a **default priority method** via the `$priorityDefaultMethod` parameter in the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function.

In the `tagged_services.php` configuration file, register the `'tags.ordered'` tag for PHP classes using the `bindTag()` method.

::: code-group

<<< @/_code/Tags/container_build_ordered.php [container_build.php]

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\Foo;

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                'tags.ordered',//[!code highlight]
                priorityDefaultMethod: 'getPriority',//[!code ++]
            )
    );
};
```

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
        ->bindTag('tags.ordered');//[!code highlight]

    yield diAutowire(Qux::class)
        ->bindTag('tags.ordered');//[!code highlight]
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

For PHP classes, declare the tag `'tags.ordered'` using the `\Kaspi\DiContainer\Attributes\Tag` attribute.

Configure the injection of a collection of elements marked with the tag `'tags.ordered'` into the `$services` parameter of the `\App\Services\Foo` class constructor by using the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute, and specify `'getPriority'` as the **default priority method** via the `$priorityDefaultMethod` parameter.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;//[!code highlight]

final class Foo
{
    public function __construct(
        #[TaggedAs(//[!code highlight]
            'tags.ordered',//[!code highlight]
            priorityDefaultMethod: 'getPriority',// [!code ++]
        )]//[!code highlight]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;//[!code highlight]

#[Tag('tags.ordered')]//[!code highlight]
final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;//[!code highlight]

#[Tag('tags.ordered')]//[!code highlight]
final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;//[!code highlight]

#[Tag('tags.ordered')]//[!code highlight]
final class Qux
{
    // …
    public static function getPriority(): int
    {
        return 100;
    }
}
```

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
