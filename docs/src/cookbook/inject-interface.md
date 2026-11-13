---
outline: [2, 4]
title: Внедрение зависимости в параметр с типом интерфейс
---
### Внедрение зависимости в параметр с типом интерфейс { #overview }

При некоторых сценариях использования контейнера требуется возможность объявить внедрение зависимости в параметр метода или вызываемого типа[^CallableType] ожидающего объект реализующий определённый интерфейс.

Обеспечить внедрение такого объекта можно связав FQCN[^FQCN] интерфейса с конкретным PHP-классом, при этом контейнер автоматически внедряет нужный объект.

Контейнер позволяет реализовать такое объявление множеством способов, в зависимости от решаемой задачи.

> [!IMPORTANT]
> Получаемый объект PHP-класса из контейнера для внедрения должен реализовывать интерфейс ожидаемый параметром.

#### Глобальное объявление { #global }

Объявить реализацию интерфейса в контейнере конкретным PHP-классом можно глобально, т.е. контейнер будет автоматически внедрять объявленную реализацию интерфейса, если для конкретного параметра метода или вызываемого типа не объявлено внедрение другой реализации требуемого интерфейса.

Для глобального объявления реализации интерфейса необходимо объявить в качестве идентификатора контейнера[^ContainerId] имя интерфейса и его реализацию [через хелпер функцию](../documentation/php-definition/index.md#helper-fn) или **через [атрибут `\Kaspi\DiContainer\Attributes\Service`](../documentation/attribute-definition/service.md)**.

#### Объявление внедрения в конкретный параметр { #param }

Для параметра метода или вызываемого типа представленного конкретным интерфейсом можно объявить внедрение зависимости отличающуюся от глобального объявления реализации интерфейса.

Объявить внедрение зависимости в такой параметр можно через [хелпер функцию](../documentation/php-definition/index.md#helper-fn) или [атрибуты](../documentation/attribute-definition/index.md#attributes).

### Пример объявления реализации интерфейса { #example }

PHP-классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use App\Interfaces\ServiceInterface;

final class Foo
{
    public function __construct(
        public readonly ServiceInterface $service
    ) {}
}
```

```php[ServiceInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/ServiceInterface.php [!code highlight]

namespace App\Interfaces;

interface ServiceInterface
{
    // …
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

use App\Interfaces\ServiceInterface;

final class Bar implements ServiceInterface
{
    // …
}
```

:::


#### Хелпер функции { #helper-fn } 


#### Атрибуты { #attributes }


## 🚧🚧🚧🚧

--------

Необходима конфигурация контейнера для автоматического внедрения параметра `App\Services\Foo::$service`: 


Для этой задачи необходимо чтобы имя интерфейса было указано как идентификатор контейнера, и сконфигурирован PHP-класс реализующий этот интерфейс.

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Interfaces\ServiceInterface;


$container = (new DiContainerBuilder())
    ->build();

$object = $container->get(ServiceInterface::class);

var_dump(is_object($object));
// (bool) true
```

## Настройка в конфигурационных файлах определений { #php-definition }

Объявление PHP-классов реализующих конкретный интерфейс в [файле конфигураций](../documentation/container-builder/configuration_files.md).

### `diAutowire()` { #helper-di-autowire }

Для конфигурирования определения используем [хелпер функцию `diAutowire()`](../documentation/php-definition/di-autowire.md).

Конфигурирование:

::: code-group

```php [services.php]
// file: /app/config/services.php
use App\Interfaces\ServiceInterface;
use App\Services\Bar;
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {

    yield ServiceInterface::class => diAutowire(Bar::class)
        ->bindArguments(
            diParameter('params.lorem')
        );

};
```

```php [parameters.php]
// file: /app/config/parameters.php

return [
    'params.lorem' => 'Lorem ipsum',
];
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

### `diCallable()` { #helper-di-callable }

Для конфигурирования определения используем [хелпер функцию `diCallable()`](../documentation/php-definition/di-callable.md) и анонимную функцию.

Конфигурирование:

::: code-group

```php [services.php]
// file: /app/config/services.php
use App\Interfaces\ServiceInterface;
use App\Services\Bar;
use function Kaspi\DiContainer\{diCallable, diParameter};

return static function (): \Generator {

    $fn = static function (string $paramOne, string $paramTwo) {
        $calculatedParam = $paramOne . $paramTwo;
        
        $bar = new Bar($calculatedParam);
        
        // возможны дополнительные дейаствия с $bar
        
        return $bar;    
    };

    yield ServiceInterface::class => diCallable($fn)
        ->bindArguments(
            diParameter('params.one'),
            diParameter('params.two'),
        );

};
```

```php [parameters.php]
// file: /app/config/parameters.php
return [
    'params.one' => 'foo',
    'params.two' => 'bar',
];
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

### `diGet()` { #helper-di-get }

Используем [хелпер функцию `diGet()`](../documentation/php-definition/di-get.md) как указатель на ранее сконфигурированный класс.


Конфигурирование:

::: code-group

```php [services.php]
// file: /app/config/services.php
use function Kaspi\DiContainer\{diAutowire, diParameter, diGet};
use App\Services\{Bar, Foo};
use App\Interfaces\ServiceInterface;

return static function (): \Generator {

    yield diAutowire(Bar::class)
        ->bindArguments(
            diParameter('params.lorem')
        );
        
    // …
   
    yield ServiceInterface::class => diGet(Bar::class);
};
```

```php [parameters.php]
// file: /app/config/parameters.php
return [
    'params.lorem' => 'Lorem ipsum',
];
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

## PHP атрибуты { #php-attributes }

Конфигурирование через PHP атрибуты.

### Service { #attribute-service }

Атрибут `\Kaspi\DiContainer\Attributes\Service` устанавливает в контейнере какой PHP-класс будет выступать реализацией интерфейса `\App\Interfaces\ServiceInterface`.

При таком подходе конфигурирования любая зависимость с типом `\App\Interfaces\ServiceInterface`
будет внедрена с одинаковой реализацией этого интерфейса PHP-классом `\App\Services\Bar`.

::: code-group

```php [ServiceInterface.php]
// file: /app/src/Interfaces/ServiceInterface.php

namespace App\Interfaces;

use App\Services\Bar;// [!code ++]
use Kaspi\DiContainer\Attributes\Service;// [!code ++]

#[Service(Bar::class)]// [!code ++]
interface ServiceInterface
{
    // …
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use Kaspi\DiContainer\Attributes\Parameter;

final class Bar implements ServiceInterface
{
    public function __construct(
        #[Parameter('params.lorem')]
        private readonly string $param
    ) {}
}
```

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Interfaces\ServiceInterface;

final class Foo
{
    public function __construct(
        private ServiceInterface $service
    ) {}
    
    // …
    
    public function service(): ServiceInterface
    {
        return $this->service;
    }
}
```

:::

Конфигурирование параметров контейнера:
```php
// file: /app/config/parameters.php
return [
    'params.lorem' => 'Lorem ipsum',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->loadParameters('/app/config/parameters.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

### InjectByCallable { #attribute-inject-by-callable }

Конфигурация внедрения зависимости через атрибут `\Kaspi\DiContainer\Attributes\InjectByCallable`.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use App\Helpers\FactoryCreate;// [!code ++]
use Kaspi\DiContainer\Attributes\InjectByCallable;// [!code ++]

final class Foo
{
    public function __construct(
        #[InjectByCallable([FactoryCreate::class, 'makeService'])]// [!code ++]
        private ServiceInterface $service
    ) {}
    
    // …
    
    public function service(): ServiceInterface
    {
        return $this->service;
    }
}
```

```php [FactoryCreate.php]
// file: /app/src/Helpers/FactoryCreate.php

namespace App\Helpers;

use App\Services\Bar;
use Kaspi\DiContainer\Attributes\Parameter;

final class FactoryCreate
{
    public static function makeService(
        #[Parameter('params.one')]
        string $paramOne,
        #[Parameter('params.two')]
        string $paramTwo,
    ): Bar {
        $calculatedParam = $paramOne . $paramTwo;
        
        $bar = new Bar($calculatedParam);
        
        // возможны дополнительные дейаствия с $bar
        
        return $bar;    
    }
}
```

```php [ServiceInterface.php]
// file: /app/src/Interfaces/ServiceInterface.php

namespace App\Interfaces;

interface ServiceInterface
{
    // …
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

use App\Interfaces\ServiceInterface;

final class Bar implements ServiceInterface
{
    public function __construct(private readonly string $param) {}
}
```

:::

Конфигурирование параметров контейнера:

```php
// file: /app/config/parameters.php
return [
    'params.one' => 'foo',
    'params.two' => 'bar',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->loadParameters('/app/config/parameters.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

### Inject { #attribute-inject }

Конфигурация внедрения зависимости через атрибут `\Kaspi\DiContainer\Attributes\Inject`.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use Kaspi\DiContainer\Attributes\Inject;// [!code ++]

final class Foo
{
    public function __construct(
        #[Inject(Bar::class)]// [!code ++]
        private ServiceInterface $service
    ) {}
    
    // …
    
    public function service(): ServiceInterface
    {
        return $this->service;
    }
}
```

```php [ServiceInterface.php]
// file: /app/src/Interfaces/ServiceInterface.php

namespace App\Interfaces;

interface ServiceInterface
{
    // …
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use Kaspi\DiContainer\Attributes\Parameter;// [!code ++]

final class Bar implements ServiceInterface
{
    public function __construct(
        #[Parameter('params.lorem')]// [!code ++]
        private readonly string $param
    ) {}
}
```

:::

Конфигурирование параметров контейнера:

```php
// file: /app/config/parameters.php
return [
    'params.lorem' => 'Lorem ipsum',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->loadParameters('/app/config/parameters.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

<!--@include: ../documentation/_include/term_notes.md-->