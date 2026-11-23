---
outline: [2, 5]
title: Declaration of an insertion into a specific parameter
---
### Example of implementation for a specific parameter { #overview }

When declaring an injection into a method parameter or a callable type[^CallableType] that expects an object implementing a specific interface, you can specify exactly which implementation should be injected. In such a declaration, the PHP class being injected may differ from the [globally declared implementation of the interface](index.md#global).

### PHP classes { #php-classes }

::: code-group
<<< @/_code/cookbook/inject-interface/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/inject-interface/src/Services/Foo.php
<<< @/_code/cookbook/inject-interface/src/Services/Qux.php
<<< @/_code/cookbook/inject-interface/src/Services/Bar.php
<<< @/_code/cookbook/inject-interface/src/Services/Baz.php
:::

You need to register the `\App\Services\Bar` PHP class as an implementation of the `\App\Interfaces\QuxInterface` interface, so that the container can automatically inject it into method parameters based on the configuration.

Additionally, for the `\App\Services\Qux` PHP class, you need to configure dependency injection into the `$qux` constructor parameter of the `\App\Services\Baz` PHP class; note that the class being injected implements the `\App\Interfaces\QuxInterface` interface.

#### Helper functions { #helper-fn-arg }

In the `services.php` configuration file, the primary implementation of the `\App\Interfaces\QuxInterface` interface is specified as the PHP class `\App\Services\Bar`.

::: code-group
<<< @/_code/cookbook/inject-interface/services.php
:::

This means that an object of the PHP class `\App\Service\Bar` will be injected into any parameter with the type `\App\Interfaces\QuxInterface`.

##### diAutowire()

The configuration file `services_qux.php` uses the helper function [`diAutowire()`](../../documentation/php-definition/di-autowire.md) to declare injection into the `$qux` constructor parameter of the PHP class `\App\Services\Qux`.

> [!NOTE]
> Here is a simple example of injection via a helper function. When defining the injection of a PHP class using a helper function, all the features of that helper function are available — including [constructor argument binding](../../documentation/php-definition/di-autowire.md#bindarguments) and injection via [setter methods](../../documentation/php-definition/di-autowire.md#di-autowire-setter-method).

::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\diAutowire;//[!code ++]

return static function (): Generator {
    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diAutowire(Baz::class)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->

---

##### diCallable()

In some cases, when complex initialization of a PHP class is required, the process of creating such an object can be delegated to any callable type[^CallableType].

The configuration file `services_qux.php` uses the helper function [`diCallable()`](../../documentation/php-definition/di-callable.md) to declare a PHP class via an anonymous function.

::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\{diAutowire, diCallable};//[!code ++]

return static function (): Generator {
    // Сложная сборка PHP-класса
    $fn = static function (): Baz {
        $baz = new Baz();
        // Additional operations with $baz are possible here.
        return $baz;
    };

    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diCallable($fn)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->


> [!TIP]
> A similar declaration can be made using the helper function [`diFactory()`](../../documentation/php-definition/di-factory.md).

---

##### diGet()

If a PHP class definition — complete with all necessary dependency injections and configurations — already exists in the container and is accessible via its `get()` method, then when defining an implementation for an interface, you can simply provide a reference to the identifier for that PHP class as it exists within the container.

The configuration file `services_qux.php` uses the helper function [`diGet()`](../../documentation/php-definition/di-get.md) to declare an injection into a parameter.

::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\{diAutowire, diGet};//[!code ++]

return static function (): Generator {
    yield diAutowire(Baz::class);//[!code highlight]

    // … many lines

    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diGet(Baz::class)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->

#### Attributes { #attributes-arg }

The implementation of the `\App\Interfaces\QuxInterface` interface is declared by the PHP class `\App\Services\Bar` globally via the [`\Kaspi\DiContainer\Attributes\Service`](../../documentation/attribute-definition/service.md) attribute.

::: code-group
```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php

namespace App\Interfaces;

use App\Service\Bar;//[!code ++]
use Kaspi\DiContainer\Attributes\Service;//[!code ++]

#[Service(Bar::class)]//[!code ++]
interface QuxInterface
{
    // …
}
```
:::

This means that an object of the PHP class `\App\Service\Bar` will be injected into any parameter with the type `\App\Interfaces\QuxInterface`.

---

##### Autowire

The `$qux` constructor parameter of the PHP class `\App\Services\Qux` is typed as `\App\Interfaces\QuxInterface`. You need to specify that a different implementation of this interface — specifically, the PHP class `\App\Services\Baz` — should be injected into this parameter. This can be done using the [`\Kaspi\DiContainer\Attributes\Autowire`](../../documentation/attribute-definition/autowire.md) attribute.

> [!NOTE]
> Here is a simple example of using an attribute. When declaring the injection of a PHP class via an attribute, all of the attribute's features are available, including [constructor argument declarations](../../documentation/attribute-definition/autowire.md#arguments) and injections via [setter methods](../../documentation/attribute-definition/autowire.md#setups).

::: code-group
```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

final class Qux
{
    public function __construct(
        #[Autowire(Bar::class)]//[!code ++]
        public readonly QuxInterface $qux
    ) {}
}
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_build_attr.php [container_build.php]
:::

<!--@include: _include/inject-interface-arg-result.md-->

---

##### InjectByCallable

Declaring an interface implementation can require complex PHP class assembly, so this process can be delegated to any callable type[^CallableType].

In this example, the called type is the static method of the PHP class - `\App\Helpers\FactoryCreate::makeService()`, and the injection can be declared using the attribute [`\Kaspi\DiContainer\Attributes\InjectByCallable`](../../documentation/attribute-definition/inject-by-callable.md).

::: code-group
```php [FactoryCreate.php]
<?php//[!code ++]
declare(strict_types=1);//[!code ++]

// file: /app/src/Helpers/FactoryCreate.php [!code highlight]

namespace App\Helpers;//[!code ++]

use App\Services\Baz;//[!code ++]

final class FactoryCreate//[!code ++]
{//[!code ++]
    // Complex PHP class construction//[!code ++]
    public static function makeService(): Baz//[!code ++]
    {//[!code ++]
        $baz = new Baz();//[!code ++]
        // Additional operations with $baz are possible here. //[!code ++]
        return $baz;//[!code ++]
    }//[!code ++]
}//[!code ++]
```
```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\InjectByCallable;//[!code ++]
use App\Helpers\FactoryCreate;//[!code ++]

final class Qux
{
    public function __construct(
        #[InjectByCallable([//[!code ++]
            FactoryCreate::class,//[!code ++]
            'makeService',//[!code ++]
        )]//[!code ++]
        public readonly QuxInterface $qux
    ) {}
}
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_build_attr.php [container_build.php]
:::

<!--@include: _include/inject-interface-arg-result.md-->

---

##### Inject

If a PHP class has already been registered in the container — with all necessary injections and configurations specified — and is accessible via the container's `get()` method, you can use the [`\Kaspi\DiContainer\Attributes\Inject`](../../documentation/attribute-definition/inject.md) attribute to declare an injection into a parameter.

::: code-group
```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Inject;//[!code ++]

final class Qux
{
    public function __construct(
        #[Inject(Bar::class)]//[!code ++]
        public readonly QuxInterface $qux
    ) {}
}
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_build_attr.php [container_build.php]
:::

<!--@include: _include/inject-interface-arg-result.md-->


<!--@include: ../../documentation/_include/term_notes.md-->
