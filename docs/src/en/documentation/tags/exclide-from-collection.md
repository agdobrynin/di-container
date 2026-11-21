---
outline: [2, 5]
title: Removing elements from a collection
---
### Removing elements from a collection { #title }

Collections can exclude elements. An element can be excluded from a collection based on its container identifier[^ContainerId] or by specifying the calling PHP class that is, the class itself.

### Exclusion from a collection by container identifier { #exclude-by-id }

To exclude elements from a collection, use the `$containerIdExclude` parameter
in the [`diTaggedAs()`](../php-definition/di-tagged-as.md) helper function and the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) attribute.

The `$containerIdExclude` parameter can contain the container identifiers of the elements to be excluded from the injected collection.

#### An example of removing an item from a collection { #example-exclude-by-id }

PHP classes:

::: code-group

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

:::


The PHP classes `\App\Service\Bar`, `\App\Service\Baz`, and `\App\Service\Qux` will be marked with the tag `'tags.internal_service'`.

A collection of elements tagged with `'tags.internal_service'` must be injected into the `$services` parameter of the PHP class `\App\Service\Foo`. However, the collection element with the container identifier `'App\Services\Baz'` must be excluded from this collection; the element can be retrieved via the container method <nobr>`$container->get(App\Services\Baz::class)`</nobr>.

##### Declaration in configuration files { #example-exclude-by-id-php-definitions }

[In the configuration file](../container-builder/configuration_files.md) `services.php` in the `$containerIdExclude` parameter of the `diTaggedAs()` helper function, specify the `'App\Services\Baz'` identifier to exclude from the collection.

In the `tagged_services.php` configuration file, declare the tag `'tags.internal_service'` for the remaining PHP classes using the `bindTag()` method.

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
    ->load('/app/config/tagged_services.php.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
} 
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\{Foo, Bar, Baz, Qux};

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            services: diTaggedAs(
                tag: 'tags.internal_service',//[!code highlight]
                containerIdExclude: [//[!code ++]
                    Baz::class,//[!code ++]
                ],//[!code ++]
            )
        );
        
    yield diAutowire(Bar::class)
        ->bindTag('tags.internal_service');//[!code highlight]
        
    yield diAutowire(Baz::class)
        ->bindTag('tags.internal_service');//[!code highlight]
        
    yield diAutowire(Qux::class)
        ->bindTag('tags.internal_service');//[!code highlight]
};
```

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php //[!code highlight]

use App\Services\{Bar, Baz, Qux};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag('tags.internal_service');//[!code highlight]
        
    yield diAutowire(Baz::class)
        ->bindTag('tags.internal_service');//[!code highlight]
        
    yield diAutowire(Qux::class)
        ->bindTag('tags.internal_service');//[!code highlight]
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
string(16) "App\Services\Qux"
```

##### Declaration via attributes { #example-exclude-by-id-php-attributes }

Use the `$containerIdExclude` parameter of the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) attribute to specify the identifier `'App\Services\Baz'` for exclusion from the collection.

Declare the `'tags.internal_service'` tag for other PHP classes using the [`\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) attribute.

::: code-group

<<< @/_code/Tags/src/Services/FooAttr.php [Foo.php]

<<< @/_code/Tags/src/Services/BarAttr.php [Bar.php]

<<< @/_code/Tags/src/Services/BazAttr.php [Baz.php]

<<< @/_code/Tags/src/Services/QuxAttr.php [Qux.php]

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
    ->import(namespace: 'App\\', src: '/app/src/')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
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
string(16) "App\Services\Qux"
```

### Excluding the calling class { #exclide-self }

A collection of elements may contain a PHP class that itself injects that same collection. To prevent this behavior, refer to the `$selfExclude` parameter in the [`diTaggedAs()`](../php-definition/di-tagged-as.md) helper function and the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) attribute. By default, this parameter is set to `true`.

If the calling PHP class should be included in the collection, you must set the `$selfExclude` parameter to `false`.

::: code-group

```php [helper function diTaggedAs()]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\ClassTaggedAs;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(ClassTaggedAs::class)
        ->bindTag('tags.foo')//[!code warning]
        ->bindArguments(
            items: diTaggedAs(
                'tags.foo',//[!code highlight]
                selfExclude: false,//[!code ++]
            )
        );
};
```

```php [TaggedAs attribute]
<?php
declare(strict_types=1);

// file: /app/src/Services/ClassTaggedAs.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\{Tag, TaggedAs}; 

#[Tag('tags.foo')]//[!code warning]
final class ClassTaggedAs
{
    public function __construct(
        #[TaggedAs(
            'tags.foo',//[!code highlight]
            selfExclude: false,//[!code ++]
        )]
        public readonly iterable $items
    ) {}
}
```

:::

<!--@include: ../_include/term_notes.md-->
