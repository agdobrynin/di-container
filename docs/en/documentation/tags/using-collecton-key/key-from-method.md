---
outline: [2, 4]
title: Collection's identifier method
---
### Collection's identifier method { #key-from-method }

The identifier of a collection element represented by a PHP class can be retrieved via a specific method within that class referred to as an "identifier method." This method is declared using [tag metadata](../tag-metadata.md).

The value within the tag metadata is specified as a key-value pair:
- **key** – the name of the metadata within the tag.
- **value** – the "identifier method," prefixed with `self::`.

A collection can be injected using the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function or the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute, by passing the **metadata name** as the `$key` parameter.

<!--@include: _include/key_in_metadata_defined.md-->

Tag metadata is passed via the `$options` parameter of the `bindTag()`[^BindTag]  method or the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute.

<!--@include: _include/key_from_method.md-->

<!--@include: _include/important_key_equals.md-->

### Example of extracting an element identifier from an "identifier method" { #example-key-from-method }

PHP classes:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

It is necessary to inject a lazy collection into the `$services` constructor parameter of the PHP class `\App\Services\Foo`. This collection should consist of elements marked with the tag `'tags.internal'`. For the PHP class `\App\Services\Bar`, the collection element identifier must be derived using the "identifier method" `\App\Services\Baz::getKey()`, which should be specified in the tag metadata under the name `'key_as'`.

We will implement the `\App\Services\Baz::getKey()` method, which returns the identifier for an element in the collection.

::: code-group
```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

final class Baz
{
    // …
    
    public static function getKey(string $tag, array $options): string//[!code ++]
    {//[!code ++]
        return 'id_baz';//[!code ++]
    }//[!code ++]
}
```
:::

#### Declaration in configuration files { #example-key-from-method-php-definitions }

In the `services.php` configuration file, declare the injection of a collection of elements marked with the `'tags.internal'` tag into the `$services` constructor parameter, using the `diTaggedAs()` helper function with the `$key` parameter set to `'key_as'`.

In the `tagged_services.php` configuration file, assign the `'tags.internal'` tag to PHP classes using the `bindTag()` method; additionally, for the `\App\Services\Baz` PHP class, specify extra metadata via the `$options` parameter.

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
        ->bindTag('tags.internal');//[!code ++]

    yield diAutowire(Baz::class)
        ->bindTag(//[!code ++]
            'tags.internal',//[!code ++]
            options: [//[!code ++]
                'key_as' => 'self::getKey',//[!code ++]
            ],//[!code ++]
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
string(16) "App\Services\Bar"
string(16) "App\Services\Bar"

string(15) "id_baz"
string(16) "App\Services\Baz"
```

#### Declaration via attributes { #example-key-from-method-php-attributes }

Declare the injection of a collection of elements marked with the `'tags.internal'` tag into the `$services` constructor parameter using the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute, with the `$key` parameter set to `'key_as'`.

We will assign the `'tags.internal'` tag to PHP classes using the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute; for the `\App\Services\Baz` PHP class, we will also provide additional metadata via the `$options` parameter.

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

<<< @/_code/TagsId/src/Services/BarAttr.php [Bar.php]

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.internal',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'self::getKey',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Baz
{
    // …
    
    public static function getKey(string $tag, array $options): string
    {
        return 'id_baz';
    }
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

string(15) "id_baz"
string(16) "App\Services\Baz"
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->