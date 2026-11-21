---
outline: [2, 4]
title: Element identifier as an integer
---
### Element identifier as an integer { #key-as-int }

A collection being injected can be represented as a simple list, where element identifiers are integers ranging from `0` to `n`.

When declaring the injection of such a list-like collection, you must set the `$useKeys` parameter to `false` within the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function or the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute.

> [!WARNING]
> Declaring the implementation of a collection/list means that other parameters of the helper function or attribute—those responsible for extracting the identifiers of the collection's elements—will be ignored.

### Example of implementing a list collection { #example }

PHP classes:

::: code-group
<<< @/_code/TagsId/src/Services/Foo.php
<<< @/_code/TagsId/src/Services/Bar.php
<<< @/_code/TagsId/src/Services/Baz.php
:::

It is necessary to inject a collection specifically a list—into the `$services` constructor parameter of the PHP class `\App\Services\Foo`, where the identifiers of the collection elements are represented by integers ranging from `0` to `n`.

#### Declaration in configuration files { #example-php-definition }

In the `services.php` configuration file, declare the injection of a collection of elements marked with the `'tags.internal'` tag into the `$services` constructor parameter using the [`diTaggedAs()`](../../php-definition/di-tagged-as.md) helper function, with the `$useKeys` parameter set to `false`.

In the `tagged_services.php` configuration file, assign the `'tags.internal'` tag to PHP classes using the `bindTag()`[^BindTag] method.

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

use App\Services\Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(//[!code ++]
                tag: 'tags.internal',//[!code ++]
                useKeys: false,//[!code ++]
            )//[!code ++]
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
int(0)
string(16) "App\Services\Bar"

int(1)
string(16) "App\Services\Baz"
```

#### Declaration via attributes { #example-attributes }

Declare the injection of a collection of elements marked with the `'tags.internal'` tag into the `$services` parameter of the PHP class `\App\Services\Foo` using the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) attribute, with the `$useKeys` parameter set to `false`.

We will assign the `'tags.internal'` tag to PHP classes using the [`\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) attribute.

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
        #[TaggedAs(// [!code ++]
            'tags.internal',// [!code ++]
            useKeys: false,// [!code ++]
        )]// [!code ++]
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
use App\Services\{Foo, Bar, Baz};

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
int(0)
string(16) "App\Services\Bar"

int(1)
string(16) "App\Services\Baz"
```

<!--@include: ../../php-definition/_include/bind_notes.md-->
