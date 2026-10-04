---
outline: [2, 4]
---

# diCallable

## Обзор { #overview }

Хелпер функция для конфигурирования `callable` типа (_вызываемого типа_) и автоматическим внедрением параметров функции.

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\{
    DiDefinitionArgumentsInterface as Args,
    DiDefinitionTagArgumentInterface as Tag,
};

\Kaspi\DiContainer\diCallable(
    callable $definition,
    ?bool $isSingleton = null
): Args & Tag
```

Параметры:
- `$definition` – вызываемый тип.
- `$isSingleton` – возвращать один и тот же результат (паттерн singleton). Если значение null, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

Функция `diCallable()` возвращает объект предоставляющий методы:
- `bindArguments()` – указать аргументы для параметров функции.
- `bindTag()` – добавляет тег с мета-данными для определения.

## bindArguments()

Передача аргументов для параметров вызываемого типа (_функции_).

<!--@include: ./_include/bind_arguments.md-->

Для указания неполного списка аргументов можно использовать именованные аргументы параметров:

```php
use App\Services\Foo;
use function Kaspi\DiContainer\diCallable;

diCallable([Foo::class, 'factoryMethod'])
    ->bindArguments(name: 'Lorem ipsum');
```

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

final class Foo {
    // …
    
    public static function factoryMethod(Bar $bar, string $name) {}
}
```

```php [Bar.php]
//file: /app/src/Services/Bar.php

namespace App\Services;

final class Bar {
    // …
}
```

:::

## bindTag()

Теги позволяют отнести конфигурируемый вызываемый тип к коллекции сервисов.

<!--@include: ./_include/bind_tag.md-->

## Идентификатор контейнера { #container-id }

Хелпер функция `diCallable()` не может автоматически сформировать идентификатор контейнера,
поэтому необходимо указать идентификатор контейнера для определения конфигурируемого через эту хелпер функцию.

Конфигурирование контейнера и PHP классы:

::: code-group

```php [services.php]
// file: /app/config/services.php

use App\Services\ServiceFoo;
use function \Kaspi\DiContainer\{diCallable, diParameter};

return static function (): \Generator {
    
    // callable тип – анонимная функция с параметром
    $fn = static fn (string $apiKey) => new ServiceFoo($apiKey, false);

    // Указываем идентификатор контейнера 'services.foo.one'
    yield 'services.foo.one' => diCallable($fn)
        // Аргумент для анонимной функции $fn($apiKey)
        ->bindArguments(
            diParameter('api_key')
        );

    // Указываем идентификатор контейнера 'services.foo.two'
    yield 'services.foo.two' => diCallable([ServiceFoo::class, 'makeWithDebug'])
        // Аргумент для параметра ServiceFoo::makeWithDebug($apiKey)
        ->bindArguments(
            apiKey: diParameter('api_key.for_debug')
        );
};
```

```php [api_keys.php]
// file: /app/config/params/api_keys.php

return [
    'api_key' => 'value_api_key',
    'api_key.for_debug' => 'other_value_api_key',    
];
```

```php [ServiceFoo.php]
// file: /app/src/Services/ServiceFoo.php

namespace App\Services;

class ServiceFoo {

    public function __construct(
        public readonly string $apiKey,
        public readonly bool $debug
    ) {}

    // `callable` [ServiceFoo::class, 'makeWithDebug'] 
    public static function makeWithDebug(string $apiKey): self {
        return new self($apiKey, true)
    }
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ServiceFoo;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/params/api_keys.php')
    ->load('/app/config/services.php')
    ->build()
;

// ...
$serviceOne = $container->get('services.foo.one');

var_dump($serviceOne instanceof ServiceFoo);
// bool(true)

var_dump($serviceOne->apiKey, $serviceOne->debug);
// string(13) "value_api_key"
// bool(false)

$serviceTwo = $container->get('services.foo.two');

var_dump($serviceTwo instanceof ServiceFoo);
// bool(true)

var_dump($serviceTwo->apiKey, $serviceTwo->debug);
// string(19) "other_value_api_key"
// bool(true)

```

## Преобразуемые объявления { #convert-to-di-callable } 

Если нет необходимости указывать аргументы или теги для определения контейнера,
то в [конфигурационном файле](../container-builder/configuration_files.md) можно указать вызываемый тип «как-есть»,
без применения хелпер функции.

::: code-group

```php [services.php]
// file: /app/config/services.php

use App\Services\ServiceFoo;

return static function (): \Generator {
   // 1️⃣ анонимная функция
   yield 'services.foo.one' => static fn () => new ServiceFoo(apiKey: 'value_api_key', debug: false);

   // 2️⃣ статический метод класса
   yield 'services.foo.two' =>  [ServiceFoo::class, 'makeForTest'];
   
   // 3️⃣ функция
   yield 'services.foo.three' =>  'App\Functions\staging_service';
};
```

```php [ServiceFoo.php]
// file: /app/src/Services/ServiceFoo.php

namespace App\Services;

class ServiceFoo {

    public function __construct(
        public readonly string $apiKey,
        public readonly bool $debug
    ) {}

    // `callable` [ServiceFoo::class, 'makeWithDebug'] 
    public static function makeWithDebug(string $apiKey): self {
        return new self($apiKey, true)
    }
}
```

```php [functions.php]
// file: /app/src/Functions/functions.php

namespace App\Functions;

use App\Services\ServiceOne;

function staging_service(bool $debug = true): ServiceOne {
    $apiKey = \getenv('APP_API_KEY_STAGING');

    return new ServiceOne($apiKey, $debug);
}

```

:::

Определения указанные в конфигурационном файле `config/services.php` будут 
автоматически объявлены так как будто их сделали через хелпер функцию `diCallable()`.
