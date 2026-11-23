---
outline: [2, 4]
title: Default "identifier method" for a collection element
---
### Default "identifier method" for a collection element { #key-from-method-default }

For collection elements represented by a PHP class, the **default collection element identifier method** serves as a fallback mechanism for retrieving an element's identifier when no such configuration is provided in the [tag metadata](../tag-metadata.md).

If the **default collection element identifier method** is not implemented within the PHP class, the identifier value will default to the container identifier[^ContainerId] of that element.

A collection can be injected using the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function or the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute, with the method name specified via the `$keyDefaultMethod` parameter.

<!--@include: _include/key_from_method.md-->
<!--@include: _include/important_key_equals.md-->

### Example of extracting an item identifier from the default collection item's "identifier method" { #key-from-method-default-default }

PHP classes:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

It is necessary to inject a lazy collection of elements tagged with `'tags.internal'` into the `$services` constructor parameter of the PHP class `\App\Services\Foo`, specifying the `getCustomIndex()` fallback method to retrieve the container identifier for each collection element.

We will implement the `\App\Services\Baz::getCustomIndex()` method, which returns the identifier for a collection element.

::: code-group
```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

final class Baz
{
    // …

    public static function getCustomIndex(): string //[!code ++]
    {//[!code ++]
        return 'baz_custom_id';//[!code ++]
    }//[!code ++]
}
```
:::

#### Declaration in configuration files { #key-from-method-default-default-php-definition }

In the `services.php` configuration file, declare the injection of a collection of elements marked with the tag `'tags.internal'` into the `$services` constructor parameter, using the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function with the `$keyDefaultMethod` parameter set to `'getCustomIndex'`.

In the `tagged_services.php` configuration file, assign the tag `'tags.internal'` to PHP classes using the `bindTag()`[^BindTag] method.

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
                keyDefaultMethod: 'getCustomIndex',//[!code ++]
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
        ->bindTag('tags.internal');//[!code ++]

    yield diAutowire(Baz::class)
        ->bindTag('tags.internal');//[!code ++]
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(16) "App\Services\Bar"
string(16) "App\Services\Bar"

string(15) "baz_custom_id"
string(16) "App\Services\Baz"
```

#### Declaration via attributes { #key-from-method-default-default-attributes }

For PHP classes, declare the `'tags.ordered'` tag using the `\Kaspi\DiContainer\Attributes\Tag` attribute (see [here](../../attribute-definition/tag.md)).

Configure the injection of a collection of elements marked with the `'tags.ordered'` tag into the `$services` parameter of the `\App\Services\Foo` class constructor, using the `\Kaspi\DiContainer\Attributes\TaggedAs` attribute (see [here](../../attribute-definition/tagged-as.md)) with the `$keyDefaultMethod` parameter set to `'getCustomIndex'`.

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
            keyDefaultMethod: 'getCustomIndex',// [!code ++]
        )]// [!code ++]
        public readonly iterable $services
    ) {}
}
```

<<< @/_code/TagsId/src/Services/BarAttr.php [Bar.php]

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;//[!code ++]

#[Tag('tags.internal')]//[!code ++]
final class Baz
{
    // …

    public static function getCustomIndex(): string//[!code ++]
    {//[!code ++]
        return 'baz_custom_id';//[!code ++]
    }//[!code ++]
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
string(16) "App\Services\Bar"
string(16) "App\Services\Bar"

string(15) "baz_custom_id"
string(16) "App\Services\Baz"
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->
