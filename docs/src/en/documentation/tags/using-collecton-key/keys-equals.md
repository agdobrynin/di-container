---
outline: [2, 4]
title: Intersection of element identifiers in a collection
---
### Intersection of element identifiers in a collection { #keys-equals }

By default, collection uses the container ID[^ContainerId] of an item as its identifier;
however, custom identifiers for collection items can be specified via [tag metadata](../tag-metadata.md).

If multiple items in a collection share the same identifier, the item with the higher [priority](../collection-priority/index.md) is included in the collection, while other items with the same identifier are ignored.

### Example { #keys-equals-example }

Let’s look at an example of registering the `'tags.internal'` tag for PHP classes, where the collection element identifier is defined via tag metadata, with the identifier value `'id_service'` being the same for all registered tags.

For the collection element `\App\Services\Baz`, a priority value of `100` is used, while for the collection element `\App\Services\Bar`, the default priority value of `null` is applied.

PHP classes:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

When a collection is injected into the `$services` constructor parameter of the PHP class `\App\Services\Foo`, the result is a collection containing a single element: the PHP class `\App\Services\Baz`. This occurs because, given that both items in the collection share the same identifier (`'id_service'`), the priority assigned to `\App\Services\Baz` via the `$priority` parameter is higher than that of `\App\Services\Bar`.

#### Declaration in configuration files { #keys-equals-example-php-definition }

In the `services.php` configuration file, declare the injection of a collection of elements marked with the `'tags.internal'` tag into the `$services` constructor parameter. This is done using the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function, with the `$key` parameter set to `'key_as'`, which specifies the metadata key used to extract the element's identifier from the tag.

In the `tagged_services.php` configuration file, define the `'tags.internal'` tag and its associated metadata within the `$options` parameter for the relevant PHP classes, using the `bindTag()`[^BindTag] method. The metadata key `'key_as'` determines the identifier that will be assigned to each element associated with that tag.

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

foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
    echo PHP_EOL;
}
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
            servies: diTaggedAs(//[!code ++]
                tag: 'tags.internal',//[!code ++]
                key: 'key_as',//[!code ++]
            )//[!code ++]
        );
};
```

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php //[!code highlight]

use App\Services\{Bar, Baz};

use function \Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag(//[!code ++]
            'tags.internal',//[!code ++]
            options: [//[!code ++]
                'key_as' => 'id_service',//[!code warning]
            ],//[!code ++]
        );//[!code ++]

    yield diAutowire(Baz::class)
        ->bindTag(//[!code ++]
            'tags.internal',//[!code ++]
            options: [//[!code ++]
                'key_as' => 'id_service',//[!code warning]
            ],//[!code ++]
            priority: 100,//[!code warning]
        );//[!code ++]
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(15) "id_service"
string(16) "App\Services\Baz"
```

#### Declaration via attributes { #keys-equals-example-attributes }

Configure the injection of a collection of elements marked with the `'tags.internal'` tag into the `$services` parameter of the PHP class `\App\Services\Foo` using the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute. The `$key` parameter is set to `'key_as'`, which specifies the metadata key used to extract the element's identifier from the tag.

We will define the `'tags.internal'` tag and its associated metadata for PHP classes using the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute. The metadata key `'key_as'` for each tag determines the identifier assigned to that specific element.

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs(// [!code ++]
            name: 'tags.internal',// [!code ++]
            key: 'key_as',// [!code ++]
        )]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.internal',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_service',// [!code warning]
    ],// [!code ++]
)]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.internal',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_service',// [!code warning]
    ],// [!code ++]
    priority: 100,// [!code warning]
)]// [!code ++]
final class Baz
{
    // …
}
```

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

foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
    echo PHP_EOL;
}
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(15) "id_service"
string(16) "App\Services\Baz"
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->