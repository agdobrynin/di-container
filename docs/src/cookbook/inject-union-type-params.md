---
outline: [2, 3]
---
# Внедрение зависимостей для параметров объединенного типа

## Обзор { #overview }

Для объединенного типа (_union type_) контейнер попытается найти доступные определения, и если будет найдено несколько вариантов внедрения зависимости то будет выброшено исключение,
которое сообщит о необходимости уточнить тип внедряемого аргумента.

<span id="src-class"/>PHP Классы:

::: code-group

```php [Service.php]
// file: /app/src/Services/Service.php
namespace App\Services;

class Service
{ 
    public function __construct(
        public readonly One | Two $dependency
    ) {}

}
```

```php [One.php]
// file: /app/src/Services/One.php
namespace App\Services;

class One {}

```

```php [Two.php]
// /app/src/Services/Two.php
namespace App\Services;

class Two {}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Service;

$container = (new DiContainerBuilder())->build();

$container->get(Service::class); // [!code error]
// throw exception 
```

Будет выброшено исключение:

> [!WARNING] PHP Fatal error:
> Uncaught Kaspi\DiContainer\Exception\AutowireParameterTypeException:
> Cannot automatically resolve dependency in App\Services\Service::__construct().
> Please specify the Parameter #0 [ \<required\> App\Services\One | App\Services\Two $dependency ].

Для устранения ошибки необходимо конкретизировать тип аргумента для параметра `$dependency`.

## PHP определения { #php-definition }

Конкретизирую тип для параметра `\App\Services\Service::$dependency` через [хелпер функцию `diGet()`](../documentation/php-definition/di-get.md).

Конфигурирование:

```php
// /app/config/services.php
use App\Services\{Service, Two};

return static function (): \Generator {
    
    yield diAutowire(Service::class)
        ->bindArguments(
            dependency: diGet(Two::class)
        );
  
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Service, Two};

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build();

$service = $container->get(Service::class);

var_dump($service->dependency instanceof Two);
// (bool) true
```

## PHP атрибуты { #php-attributes }

Конкретизирую тип для параметра `\App\Services\Service::$dependency` через PHP атрибут `\Kaspi\DiContainer\Attributes\Inject`.

```php
// file: /app/src/Services/Service.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Inject;

class Service
{ 
    public function __construct(
        #[Inject(Two::class)]
        public readonly One | Two $dependency
    ) {}

}
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Service, Two};

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->build();

$service = $container->get(Service::class);

var_dump($service->dependency instanceof Two);
// (bool) true
```
