---
outline: [2,4]
title: SetupPriority attribute
---
### SetupPriority attribute

The `\Kaspi\DiContainer\Attributes\SetupPriority` attribute modifies the order in which dependencies are injected via the setter methods of a PHP class; it is used in conjunction with the [`Setup`](setup.md) and [`SetupImmutable`](setup-immutable.md) attributes.

Attribute configuration:
- `\Attribute::TARGET_METHOD`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\SetupPriority::__construct(
    int $priority = 0
)
```

Parameters:
- `$priority` – call priority.

The higher the value of the `$priority` parameter, the earlier the dependency will be injected via a setter method.

### Example of changing the priority of setter methods { #example }

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use function array_values;

final class Foo
{
    private array $injectedClass = [];

    // …

    public function bar(Bar $bar): void
    {
        $this->injectedClass[Bar::class] = $bar;
    }

    public function baz(Baz $baz): void
    {
        $this->injectedClass[Baz::class] = $baz;
    }
    
    public function getInjectedClass(): array
    {
        return array_values($this->injectedClass);
    }
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

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

final class Baz
{
    // …
}
```

:::

Dependency injection must be configured via setter methods for `\App\Services\Foo::bar()` and `\App\Services\Foo::baz()`.

The dependency must first be injected via the setter method `\App\Services\Foo::baz()`, followed by the injection via `\App\Services\Foo::bar()`.

Use the `\Kaspi\DiContainer\Attributes\SetupPriority` attribute on the `\App\Services\Foo::baz()` method to specify the order of injection via the setter methods.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Setup;// [!code ++]
use Kaspi\DiContainer\Attributes\SetupPriority;// [!code ++]

use function array_values;

final class Foo
{
    private array $injectedClass = [];

    // …

    #[Setup]// [!code ++]
    public function bar(Bar $bar): void
    {
        $this->injectedClass[Bar::class] = $bar;
    }

    #[Setup]// [!code ++]
    #[SetupPriority(10)]// [!code ++]
    public function baz(Baz $baz): void
    {
        $this->injectedClass[Baz::class] = $baz;
    }
    
    public function getInjectedClass(): array
    {
        return array_values($this->injectedClass);
    }
}
```

::: 

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->getInjectedClass() as $injectedClass) {
    var_dump($injectedClass::class);
}
```

:::


Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
string(16) "App\Services\Baz"
string(16) "App\Services\Bar"
```

<!--@include: ../_include/term_notes.md-->