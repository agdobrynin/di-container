---
outline: [2, 3]
---
# Внедрение зависимостей для параметров объединенного типа

## Обзор { #overview }

Для объединенного типа (_union type_) контейнер попытается найти доступные определения, и если будет найдено несколько вариантов внедрения зависимости то будет выброшено исключение,
которое сообщит о необходимости уточнить тип для аргумента.

<span id="src-class"/>PHP Классы:

::: code-group

```php
// file: /app/src/Classes/One.php
namespace App\Classes;

class One {}

```

:::

----


```php
// src/Classes/One.php
namespace App\Classes;

class One {}
```
```php
// src/Classes/Two.php
namespace App\Classes;

class Two {}
```
```php
// src/Services/Two.php
namespace App\Services;

use App\Classes\{One, Two};

class Service {
 
    public function __construct(
        private One|Two $dependency
    ) {}

}
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

$container->get(App\Services\Service::class);
```
> [!WARNING]
> Будет выброшено исключение `\Psr\Container\ContainerExceptionInterface`.
>

Для устранения ошибки необходимо конкретизировать тип для аргумента `$dependency`
при конфигурировании контейнера:
```php
// config/services.php
return static function (): \Generator {
    
    yield diAutowire(App\Services\Service::class)
        ->bindArguments(
            dependency: diGet(App\Classes\Two::class)
        );
  
};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$container->get(App\Services\Service::class);
```
> [!NOTE]
> При получении сервиса `App\Services\Service::class` в аргументе `App\Services\Service::$dependency`
> содержится класс `App\Classes\Two`