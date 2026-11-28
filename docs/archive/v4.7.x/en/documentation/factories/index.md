---
outline: [2,4]
title: Using factory methods for dependency injection
---
### Using factory methods for dependency injection { #title }

In some dependency injection scenarios, it becomes necessary to use the “factory” design pattern, delegating the creation of a required object to a dedicated PHP class – a “factory”. In such cases, the container can invoke a method on your PHP class to perform the dependency injection.

<!--@include: _include/auto_inject_params.md-->

When declaring dependency injection via a factory, the value chosen for the class does not affect the actual result returned by the factory method. The [actual name of the factory class](factory_class_container_id.md) depends solely on the object retrieved via the container's `get()` method. Nevertheless, choosing a sensible name for the factory class can make it easier to work with when configuring the container.

**A PHP class can be used as a factory:**
- A public static method of a PHP class.
- A public method of a PHP class.
- The `__invoke()` magic method implemented in a PHP class.

A PHP factory class can be used to [declare dependency injection into parameters](inject_into_params.md) for methods or callable types[^CallableType].

A PHP factory class can also be used to [declare a PHP class](declare_class.md) in cases where a simple `new Foo()` call is not sufficient and a specific object construction process is required.

To invoke a factory, you should specify the value within the `$definition` parameter of the [`diFactory()`](../php-definition/di-factory.md) helper function (used in configuration files) or via the [`\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md) attribute.

### Section Contents { #toc }

- [Arguments for a factory method](args.md)
- [Declaring a PHP class via a factory](declare_class.md)
- [Declaring dependency injection into parameters](inject_into_params.md)
- [Actual class name of the factory](factory_class_container_id.md)

### Examples of declaring a PHP factory class { #example }

Let’s look at a simple, concise example of declaring a PHP class within a container using a factory PHP class.

#### Factory with a static method { #example-static-method }

A static factory method is a callable type[^CallableType]; this means that the factory PHP class itself is not instantiated by the container. Instead, the result of invoking the static method serves as the implementation of the declared class, `\App\Services\Foo`.

Factory PHP class:

```php
<?php
declare(strict_types=1);

namespace App\Factories;

use App\Services\{Foo, Bar};

final class Factory
{
    public static function createFoo(Bar $bar): Foo
    {
        $createdObject = new Foo($bar));

        // additional configuration for $createdObject

        return $createdObject;
    }
}
```

Declaration via a helper function:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use App\Factories\Factory;

use function Kaspi\DiContainer\diFactory;//[!code highlight]

return static function () {
    yield Foo::class => diFactory(//[!code highlight]
        definition: [//[!code ++]
            Factory::class,//[!code ++]
            'createFoo',//[!code ++]
        ]//[!code ++]
    );//[!code highlight]
};
```

Declaration via an attribute:

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code highlight]
use App\Factories\Factory;

#[DiFactory(//[!code highlight]
    definition: [//[!code ++]
        Factory::class,//[!code ++]
        'createFoo',//[!code ++]
    ]//[!code ++]
)]//[!code highlight]
class Foo
{
    // …
}
```

#### Factory with non-static method { #example-method }

If your factory uses an instance method rather than a static one, the first step is to ensure that the factory's PHP class itself has been registered and is available within the container meaning it can be retrieved via the container's `get(App\Factories\Factory::class)` method.

Factory PHP class:

```php
namespace App\Factories;

use App\Services\{Foo, Bar};

final class Factory
{
    public function __construct(private Bar $bar) {}

    public function createFoo(): Foo
    {
        $createdObject = new Foo($this->bar);

        // additional configuration for $createdObject
        
        return $createdObject;
    }
}
```

Declaration via a helper function:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use App\Factories\Factory;

use function Kaspi\DiContainer\diFactory;//[!code highlight]

return static function () {
    yield Foo::class => diFactory(//[!code highlight]
        definition: [//[!code ++]
            Factory::class,//[!code ++]
            'createFoo',//[!code ++]
        ]//[!code ++]
    );//[!code highlight]
};
```

Declaration via an attribute:

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code highlight]
use App\Factories\Factory;

#[DiFactory(//[!code highlight]
    definition: [//[!code ++]
        Factory::class,//[!code ++]
        'createFoo'//[!code ++]
    ]//[!code ++]
)]//[!code highlight]
class Foo
{
    // …
}
```

#### A factory that implements the magic __invoke() method { #example-invoke }

If your factory implements the [`__invoke()`](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke) magic method, the first step is to ensure that the PHP class of the factory itself has also been declared and is available in the container, i.e. can be retrieved via the container's `get(App\Factories\FooFactory::class)` method.

The syntax for declaring a factory can be simplified to just specifying its FQCN[^FQCN].

Factory PHP class:

```php
namespace App\Factories;

use App\Services\{Foo, Bar};

final class FooFactory
{
    public function __construct(private Bar $bar) {}

    public function __invoke(): Foo
    {
        $createdObject = new Foo($this->bar);

        // additional configuration for $createdObject
        
        return $createdObject;
    }
}
```

Declaration via a helper function:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use App\Factories\FooFactory;

use function Kaspi\DiContainer\diFactory;

return static function () {
    yield Foo::class => diFactory(FooFactory::class);//[!code ++]
};
```

Declaration via an attribute:

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use App\Factories\FooFactory;//[!code highlight]

#[DiFactory(FooFactory::class)]//[!code ++]
class Foo
{
    // …
}
```

<!--@include: ../_include/term_notes.md-->