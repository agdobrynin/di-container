---
outline: [2,4]
title: Autowire attribute
---
### Autowire attribute

The `\Kaspi\DiContainer\Attributes\Autowire` attribute registers a PHP class in the container or [injects a dependency into a method parameter](#autowire-on-param).

Attribute configuration:

- `\Attribute::TARGET_CLASS`
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\Autowire::__construct(
    string $id = '',
    ?bool $isSingleton = null,
    array $arguments = [],
    array|\Kaspi\DiContainer\Attributes\Tag|null $tags = null,
    ?array $setups = null,
    callable|false|string $resetter = false,
    bool $isLazy = false,
)
```

Parameters:

- `$id` – container identifier [^ContainerId], or an empty string.
- `$isSingleton` – The container will return the same object instance (singleton pattern). If set to `null`, the value will be determined based on the [container configuration](../container-config/index.md#is-singleton-service-default).
- `$arguments`<span id="constrcut-arguments"/> – arguments for a PHP class constructor.
- `$tags` – tag with metadata.
- `$setups` – dependency injection via a setter method[^SetterMethodMutable] [^SetterMethodImmutable].
- `$resetter` – [value for resetting an object's state](../object-resetters/index.md).
- `$isLazy` – declare a "lazy object". 

> [!TIP]
> Read the documentation section on [injecting "lazy" objects via the container](../lazy-injection/index.md).


### Container identifier for a PHP class { #container-id }

When declaring a PHP class using the `\Kaspi\DiContainer\Attributes\Autowire` attribute, an empty string in the `$id` parameter is interpreted by the container as a container identifier represented by the FQCN[^FQCN] of the PHP class.

Register the PHP class `\App\Services\Foo` using an attribute, with the container identifier set to the string `'App\Services\Foo'`, and configure the injection of the scalar value `'Lorem ipsum'` into the class constructor parameter `$value`.

::: code-group
<<< @/_code/attribute-definition/autowire/Foo.php
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
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);//[!code highlight]

var_dump($foo->value);
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(11) "Lorem ipsum"
```

#### Declaring a PHP class with multiple identifiers { #container-id-multuple }

The `\Kaspi\DiContainer\Attributes\Autowire` attribute can be used to register the same PHP class in the container under different identifiers[^ContainerId].

Declare the PHP class `\App\Services\Bar` using an attribute, with the container identifier specified as the string `'App\Services\Bar'`, and define the injection of the scalar value `10` into the constructor parameter `$value`.

Declare the PHP class `\App\Services\Bar` using an attribute, with the container identifier specified as the string `'services.bar_20'`, and define the injection of the scalar value `20` into the constructor parameter `$value`.

::: code-group
<<< @/_code/attribute-definition/autowire/Bar.php
:::

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Bar;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$bar = $container->get(Bar::class);
var_dump($bar->value);


$bar20 = $container->get('services.bar_20');
var_dump($bar20->value);
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
int(10)
int(20)
```


### PHP class constructor arguments { #arguments }

The `$arguments` parameter of the `\Kaspi\DiContainer\Attributes\Autowire` attribute passes arguments to the constructor of a PHP class.

<!--@include: _include/arguments.md-->

<!--@include: _include/to_arguments.md-->

#### Example of declaring arguments for a PHP class constructor { #example }

PHP classes:

::: code-group

<<< @/_code/attribute-definition/autowire/FooService.php

<<< @/_code/attribute-definition/autowire/BarService.php

<<< @/_code/attribute-definition/autowire/BazService.php

<<< @/_code/attribute-definition/autowire/BatService.php

<<< @/_code/attribute-definition/autowire/Interfaces/QuxInterface.php

::: 

It is necessary to declare the PHP class `\App\Services\FooService` with different container identifiers and arguments.

::: code-group
<<< @/_code/attribute-definition/autowire/FooServiceAttr.php [FooService.php]
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{FooService, BarService, BazService, BatService};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->addParameters([
        'emails.bar' => 'manager@example.com',//[!code highlight]
        'emails.baz' => 'admin@example.com',//[!code highlight]
    ])
    ->build();

$fooService = $container->get(FooService::class);//[!code warning]

var_dump($fooService->bat::class);
var_dump($fooService->qux::class);
var_dump($fooService->adminEmail);

$fooServiceWithBaz = $container->get('services.foo_service.with_baz');//[!code warning]

echo PHP_EOL;

var_dump($fooServiceWithBaz->bat::class);
var_dump($fooServiceWithBaz->qux::class);
var_dump($fooServiceWithBaz->adminEmail);
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(23) "App\Services\BatService"
string(23) "App\Services\BarService"
string(19) "manager@example.com"

string(23) "App\Services\BatService"
string(23) "App\Services\BazService"
string(16) "admin@example.com
```

### Dependency injection via setter methods { #setups }

The `$setups` parameter of the `\Kaspi\DiContainer\Attributes\Autowire` attribute declares dependency injection via setter methods of a PHP class, using the [`\Kaspi\DiContainer\Attributes\Setup`](setup.md) and [`\Kaspi\DiContainer\Attributes\SetupImmutable`](setup-immutable.md) attributes.

A `null` value for the `$setups` parameter instructs the container to inject dependencies via **the `Setup` and `SetupImmutable` attributes specified on the class methods**.

A value of `[]` (an empty array) for the `$setups` parameter instructs the container **not to inject dependencies via setter methods**, even if the class methods are marked with the `Setup` or `SetupImmutable` attributes.

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as Parameter;

#[Autowire(
    setups: [//[!code highlight]
        'bar' => new Setup(//[!code ++]
            new Parameter('params.api_key')//[!code ++]
       ),//[!code ++]

        'baz' => new SetupImmutable(//[!code ++]
            new DiGet(Baz::class)//[!code ++]
        ),//[!code ++]
    ]//[!code highlight]
)]
final class Foo
{
    // …
    
    public function bar(string $apiKey): void
    {
        // …
    }
    
    public function baz(QuxInterface $qux): self
    {
        // …
    }
}
```

### Tags { #tags }

The `$tags` parameter of the `\Kaspi\DiContainer\Attributes\Autowire` attribute sets the tag and metadata for the declared PHP class using the [attribute `\Kaspi\DiContainer\Attributes\Tag`](tag.md).

A `null` value for the `$tags` parameter instructs the container to **set the tag via the `Tag` attribute specified in the PHP class being declared**.

A value of `[]` (an empty array) for the `$tags` parameter instructs the container **not to assign a tag to the PHP class being declared**, even if that class has a `Tag` attribute specified.

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;

#[Autowire(
    tags: [//[!code highlight]
        new Tag('tags.fuze'),//[!code ++]
        new Tag('tags.internal_service'),//[!code ++]
    ]//[!code highlight]
)]
final class Foo
{
    // …
}
```

### Dependency injection into a method parameter or a called type { #autowire-on-param }

You can declare the injection of a PHP class into a method parameter or a callable type[^CallableType] using the `\Kaspi\DiContainer\Attributes\Autowire` attribute.

The value for the `$id` parameter of the `Autowire` attribute can be specified as an empty string or an FQCN[^FQCN] string.

An empty string in the `$id` parameter of the `Autowire` attribute will be interpreted by the container as the type of the parameter[^TypeHints] to which the attribute is applied.

#### Example of declaring an injection into a method parameter { #example-autowire-on-param }

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

class Foo
{
    public function __construct(
        public readonly QuxInterface $qux,
        public readonly Baz $baz,
    ) {}
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface 
{
    public function __construct(public readonly string $notifyEmail) {}
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

final class Baz
{
    public function __construct(
        public readonly string $email = 'baz@localhost'
    ) {}
}
```

<<< @/_code/attribute-definition/autowire/Interfaces/QuxInterface.php

:::

For the PHP class `\App\Services\Foo`, declare the injection of `$qux` and `$baz` into the constructor parameters using the `Kaspi\DiContainer\Attributes\Autowire` attribute.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;

class Foo
{
    public function __construct(
        #[Autowire(
            id: Bar::class,// [!code ++]
            arguments: [// [!code ++]
                new DiParameter('emails.bar')// [!code ++]
            ]// [!code ++]
        )]
        public readonly QuxInterface $qux,

        #[Autowire(
            arguments: [// [!code ++]
                new DiParameter('emails.baz')// [!code ++]
            ]// [!code ++]
        )]
        public readonly Baz $baz,
    ) {}
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
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->addParameters([
        'emails.bar' => 'manager@example.com',//[!code highlight]
        'emails.baz' => 'admin@example.com',//[!code highlight]
    ])
    ->build();

$foo = $container->get(Foo::class);//[!code warning]

var_dump($foo->qux->notifyEmail);
var_dump($foo->baz->email);
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(19) "manager@example.com"
string(16) "admin@example.com"
```

<!--@include: ../_include/term_notes.md-->
