---
outline: [2, 3]
---
# Получение класса по интерфейсу

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

## Конфигурирование в стиле PHP определений { #php-definition }

### `diAutowire()` { #helper-di-autowire }

Для конфигурирования определения используем [хелпер функцию `diAutowire()`](../documentation/php-definition/di-autowire.md).

Конфигурирование:

```php
// file: /app/config/services.php
use App\Interfaces\ServiceInterface;
use App\Services\Bar;
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {

    yield ServiceInterface::class => diAutowire(Bar::class)
        ->bindArguments('Lorem ipsum');

};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
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

    yield ServiceInterface::class => diCallable($fn, isSingleton: true)
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

Можно использовать [хелпер функцию `diGet()`](../documentation/php-definition/di-get.md) как указатель на ранее сконфигурированный класс.


Конфигурирование:

```php [services.php]
// file: /app/config/services.php
use function Kaspi\DiContainer\{diAutowire, diGet};
use App\Services\{Bar, Foo};
use App\Interfaces\ServiceInterface;

return static function (): \Generator {

    yield diAutowire(Bar::class)
        ->bindArguments('Lorem ipsum');
        
    // …
   
    yield ServiceInterface::class => diGet(Bar::class);
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```
