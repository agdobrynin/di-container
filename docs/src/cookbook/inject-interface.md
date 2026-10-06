---
outline: [2, 3]
---
# Внедрение зависимости по интерфейсу

## Обзор { #overview }

При некоторых сценариях использования контейнера требуется возможность внедрять зависимости по имени интерфейса.
Для реализации внедрения необходимо связать имя интерфейса с конкретным PHP классом, чтобы контейнер автоматически внедрил нужный объект.

Необходима конфигурация контейнера для автоматического внедрения параметра `App\Services\Foo::$service`: 

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Interfaces\ServiceInterface;

final class Foo
{
    public function __construct(private ServiceInterface $service) {}
    
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

Для реализации этой задачи необходимо чтобы имя интерфейса было указано как идентификатор контейнера, и сконфигурировано само определение контейнера.

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Interfaces\ServiceInterface;


$container = (new DiContainerBuilder())
    ->build();

$object = $container->get(ServiceInterface::class);

var_dump(is_object($object));
// (bool) true
```

## PHP определения { #php-definition }

Конфигурирование в стиле PHP определений.

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

В рамках контейнера для интерфейса `\App\Interfaces\ServiceInterface` можно
определить как разрешать этот интерфейс через атрибут `\Kaspi\DiContainer\Attributes\Service`.
При таком подходе конфигурирования любая зависимость с типом `\App\Interfaces\ServiceInterface`
будет разрешена одинаково.

::: code-group

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

```php [ServiceInterface.php]
// file: /app/src/Interfaces/ServiceInterface.php

namespace App\Interfaces;

use App\Services\Bar;
use Kaspi\DiContainer\Attributes\Service;

#[Service(Bar::class)]
interface ServiceInterface
{
    
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

:::

Конфигурирование:
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
use App\Helpers\FactoryCreate;
use Kaspi\DiContainer\Attributes\InjectByCallable;

final class Foo
{
    public function __construct(
        #[InjectByCallable([FactoryCreate::class, 'makeService'])]
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

Конфигурирование:

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
use Kaspi\DiContainer\Attributes\Inject;

final class Foo
{
    public function __construct(
        #[Inject(Bar::class)]
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

:::

Конфигурирование:

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
