---
outline: [2, 4]
---
# Метод контейнера для вызываемых типов { #title }

## Обзор { #overview }

Контейнер реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface` который предоставляет метод `call()` для вызываемых типов[^CallableType] или значений преобразуемых контейнером к такому типу.

**Важная особенность метода `call()` – внедрение зависимостей в параметры вызываемых типов происходит автоматически на основании конфигурации контейнера.**

Метод `call()` может вызвать `callable` тип, что означает:
- Анонимная функция.
- Функция.
- Статический метод PHP класса.
- Метод PHP объекта.
- PHP объект реализующий [магический метод `__invoke()`](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke).
- **Метод может `call()` преобразовать** к `callable` не инициализированный PHP класс и его метод:
  - Вызов нестатического метода PHP класса.
    - `$container->call([App\Services\Foo::class, 'methodName']);`
  - Вызов [магического метод `__invoke()`](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke) в PHP классе.
    - `$container->call(App\Services\CallableService::class);`

Сигнатура метода:

```php 
\Kaspi\DiContainer\Interfaces\DiContainerCallInterface::call(
    array|callable|string $definition,
     mixed ...$argument
)
``` 

Параметры:
- `$definition` - вызываемый тип или значение преобразуемое к `callable` типу.
- `$argument` - аргументы вызываемого типа.

### Аргументы вызываемого типа { #args }

Параметр переменной длины `$argument` метода `call()` передает аргументы вызываемому типу.

При объявлении внедрения зависимости в конкретный параметр вызываемого типа, можно использовать [именованные аргументы](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments).

Параметры вызываемого типа не получившие аргументы будут внедрены контейнером самостоятельно, на основании конфигурации.


## Нестатический метод PHP класса { #example-php-class }

Получить результат вызова метода `\App\Controllers\PostController::store()` с передачей аргумента для параметра `$name`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use App\Controllers\PostController;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$result = $container->call(
    [//[!code ++]
        PostController::class,//[!code ++]
        'store'//[!code ++]
    ],//[!code ++]
    name: 'Ivan'//[!code ++]
);

var_dump($result);
// string(22) "The name 'Ivan' saved!"
```

```php [PostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/PostController.php //[!code highlight]

namespace App\Controllers;

use App\Services\ServiceOne;

use function {sprintf, var_export};

final class PostController
{
    public function __construct(private ServiceOne $serviceOne) {}
    
    public function store(string $name)
    {
        $this->serviceOne->save($name);
        
        return sprintf('The name %s saved!', var_export($name, true));
    }
}
```

<<< @/_code/src/Services/ServiceOne.php

:::

Фактически метод `call()` выполнит создание экземпляра класса `\App\Controllers\PostController` с внедрением зависимостей в конструктор и вызовет метод `\App\Controllers\PostController::store()`:

```php
// будет выполнено
(new \App\Controllers\PostController(serviceOne: new ServiceOne()))
    ->post(name: 'Ivan')
```

## Магический метод __invoke() в PHP классе { #example-php-class-invoke }

PHP класс `App\Controllers\SavePostController` реализует магический метод `__invoke()` который может быть вызван через метод контейнера `call()`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use App\Controllers\SavePostController;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$result = $container->call(
  SavePostController::class,//[!code ++]
  name: 'Ivan'//[!code ++]
);

var_dump($result);
// string(22) "The name 'Ivan' saved!"
```

```php [SavePostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/SavePostController.php //[!code highlight]

namespace App\Controllers;

use App\Services\ServiceOne;

use function {sprintf, var_export};

final class SavePostController
{
    // …

    public function __invoke(ServiceOne $serviceOne, string $name)
    {
        $serviceOne->save($name);
        
        return sprintf('The name %s saved!', var_export($name, true));
    }
}
```

<<< @/_code/src/Services/ServiceOne.php

:::

Код выше эквивалентен этому код:

```php

$savePostController = $container->get(SavePostController::class);

$result = $container->call(
    [
        $savePostController,
        '__invoke'
    ],
    name: 'Ivan'
);
```

## Функция { #example-function }

Функция `\App\Functions\one_service()` принимает два параметра, первый параметр `$service` – контейнер внедрит автоматически на основании конфигурации, второй параметр `$name` необходимо передать в методе контейнера `call()`. 

::: code-group


```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$result = $container->call(
  '\App\Functions\one_service',//[!code ++]
  name: 'Ivan'//[!code ++]
); 

var_dump($result);
// string(22) "The name 'Ivan' saved!"
```

```php [functions.php]
<?php
declare(strict_types=1);

// file: /app/src/Functions/functions.php //[!code highlight]

namespace App\Functions;

use App\Services\ServiceOne;

use function {sprintf, var_export};

if (!function_exists('App\Functions\one_service')) {
  function one_service(ServiceOne $service, string $name) {
          $service->save($name);
  
          return sprintf('The name %s saved!', var_export($name, true));
  }
}
```

<<< @/_code/src/Services/ServiceOne.php

:::


Фактически метод `call()` выполнит следующий код:

```php
\App\Functions\one_service(
    new App\Service\ServiceOne(),
    name: 'Ivan',
);
```

<!--@include: ../_include/term_notes.md-->
