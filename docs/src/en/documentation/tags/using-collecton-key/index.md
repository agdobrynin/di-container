---
outline: [2, 4]
title: Collection element identifiers
---
### Collection element identifiers { #title }

By default, a collection uses the container identifier[^ContainerId] of each item as its element identifier, which allows you to access the items in the collection in [any order](#key-access).

You can assign custom identifiers to collection elements using [tag metadata](../tag-metadata.md) or PHP class methods:
- [Element identifier as an integer](key-as-int.md).
- [Element identifier in tag metadata](key-in-metadata.md).
- [Element identifier via a method](key-from-method.md) – for collection elements that are PHP classes.
- [Default element identifier via a method](key-from-method-default.md) – for collection elements that are PHP classes.

> [!WARNING]
> When accessing an element in a collection via its string identifier, an exception of type `\Psr\Container\NotFoundExceptionInterface` may be thrown if the element is not found.

<!--@include: _include/key_from_method.md-->

### Random access to collection elements { #key-access }

When injecting a collection via the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function or the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute, the `$isLazy` parameter defaults to `true`.
This means that the injected object will be a [lazy collection](../index.md#collection-is-lazy).

A lazy collection implements the following interfaces:
- `\Iterator`
- `\Psr\Container\ContainerInterface`
- `\ArrayAccess`
- `\Countable`

Implementing these interfaces enables:
- Iterating over collection elements using the PHP `foreach` language construct.
- Accessing elements by their identifier using PHP array-style syntax — e.g. `$service['foo.name']`.
- Accessing elements via the `get()` and `has()` methods as defined in [PSR-11](https://www.php-fig.org/psr/psr-11/).

### Example of collection implementation { #example-inject-lazy-collection-with-key-assess }

PHP classes:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

It is necessary to inject a lazy collection into the `$services` constructor parameter of the PHP class `\App\Services\Foo`, with access to the collection elements via their container identifiers.

#### Declaration in configuration files { #example-inject-lazy-collection-with-key-assess-php-definitions }

Configure the injection of a lazy collection of elements marked with the `'tags.internal'` tag into the `$services` parameter of the PHP class `\App\Services\Foo` using the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function within the `services.php` configuration file. To ensure that the container identifier for each element serves as its key in the collection, the `$useKeys` parameter of the helper function should be set to `true` (which is the default value).

In the `tagged_services.php` configuration file, assign the `'tags.internal'` tag to the PHP classes that represent the elements of this collection by using the `bindTag()`[^BindTag] method.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/tagged_services.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

// accessing a collection element via methods `\Psr\Container\ContainerInterface`
$bar = $foo->services->get(Bar::class);

var_dump($bar::class);

// accessing a collection element via methods `\ArrayAccess`
$baz = $foo->services[Baz::class];

var_dump($baz::class);

// counting the number of elements in a collection via `\Countable`
var_dump(count($foo->services));
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Services\Foo;

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.internal')//[!code ++]
        );
};
```

<<< @/_code/TagsId/config/tagged_services.php

::: 

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
int(2)
```

#### Declaration via attributes { #example-inject-lazy-collection-with-key-assess-attributes }

Declare the injection of a collection of elements marked with the tag `'tags.internal'` into the `$services` parameter of the PHP class `\App\Services\Foo` using the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute. The container identifier for each element in the collection should serve as its key; this is achieved by setting the `$useKeys` attribute parameter to `true` (which is the default value).

We will assign the tag `'tags.internal'` to PHP classes using the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute.

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.internal')]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

<<< @/_code/TagsId/src/Services/BarAttr.php [Bar.php]
<<< @/_code/TagsId/src/Services/BazAttr.php [Baz.php]
:::

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

// accessing a collection element via `\Psr\Container\ContainerInterface` methods
$bar = $foo->services->get(Bar::class);

var_dump($bar::class);

// accessing a collection element via `\ArrayAccess` methods
$baz = $foo->services[Baz::class];

var_dump($baz::class);

// counting the number of elements in the collection via `\Countable`
var_dump(count($foo->services));
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
int(2)
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->
