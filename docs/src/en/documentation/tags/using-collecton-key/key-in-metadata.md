---
outline: [2, 4]
title: Element identifier in tag metadata
---
### Element identifier in tag metadata { #key-in-metadata }

Custom identifiers can be assigned to collection elements via tag [metadata](../tag-metadata.md).

Metadata within a tag is defined as a key-value pair:

- **key** – the name of the metadata within the tag.
- **value** – the identifier for the collection element.

Inject a collection using the [`diTaggedAs()` helper function](../../php-definition/di-tagged-as.md) or the [`\Kaspi\DiContainer\Attributes\TaggedAs` attribute](../../attribute-definition/tagged-as.md), passing the **metadata name** as the `$key` parameter.

<!--@include: _include/key_in_metadata_defined.md-->

Tag metadata is passed via the `$options` parameter of the `bindTag()`[^BindTag]  method or the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute.

<!--@include: _include/important_key_equals.md-->

### Example of extracting an element identifier from metadata { #example-key-in-metadata }

PHP classes:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

It is necessary to inject a lazy collection into the `$services` constructor parameter of the PHP class `\App\Services\Foo`. This collection should consist of elements marked with the tag `'tags.internal'`, where the identifier for an element corresponding to the PHP class `\App\Services\Bar` must be the string `'id_bar'`, specified within the tag metadata under the key `'key_as'`.

#### Declaration in configuration files { #example-key-in-metadata-php-definition }

In the `services.php` configuration file, declare the injection of a collection of elements marked with the `'tags.internal'` tag into the `$services` constructor parameter using the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function, with the `$key` parameter set to `'key_as'`.

In the `tagged_services.php` configuration file, assign the `'tags.internal'` tag to PHP classes using the `bindTag()`[^BindTag] method; additionally, for the PHP class `\App\Services\Bar`, specify further metadata via the `$options` parameter.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Baz};

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/tagged_services.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);
$bar = $foo->services->get('id_bar');
$baz = $foo->services->get(Baz::class);

var_dump($bar::class);
var_dump($baz::class);
var_dump(count($foo->services));
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\Foo, Bar, Baz};

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
                'key_as' => 'id_bar',//[!code ++]
            ]//[!code ++]
        );//[!code ++]

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
string(16) "App\Services\Baz"
int(2)
```

#### Declaration via attributes { #example-key-in-metadata-attributes }

Configure the injection of a collection of elements marked with the `'tags.internal'` tag into the `$services` parameter of the PHP class `\App\Services\Foo` using the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute, with the `$key` parameter set to `'key_as'`.

Assign the `'tags.internal'` tag to the PHP classes using the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute; for the PHP class `\App\Services\Bar`, additionally define metadata within the `$options` parameter.

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
        'key_as' => 'id_bar',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Bar
{
    // …
}
```

<<< @/_code/TagsId/src/Services/BazAttr.php [Baz.php]

:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);
$bar = $foo->services->get('id_bar');
$baz = $foo->services->get(Baz::class);

var_dump($bar::class);
var_dump($baz::class);
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

<!--@include: ../../php-definition/_include/bind_notes.md-->
<!--@include: ../../_include/term_notes.md-->
