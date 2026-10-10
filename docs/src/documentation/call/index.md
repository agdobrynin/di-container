# 📦 Метод контейнера для вызываемых типов { #title }

## Обзор { #overview }

Контейнер реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface` который предоставляет метод `call()` для вызываемых типов или значений которые могут быть преобразованы контейнером к `callable` типу.

**Важная особенность метода `call()` – внедрение зависимостей в параметры вызываемых типов автоматически на основании конфигурации контейнера.**

Метод `call()` может вызвать `callable` тип, что означает:
- Анонимные функции.
- Функции.
- Методы объекта и статические методы PHP класса. 
- Объект реализующий [магический метод `__invoke()`](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke)
- Дополнительно **метод может преобразовать** к `callable` типу следующие значения:
  - PHP класс с нестатическим методом.
  - PHP класс реализующий [магический метод `__invoke()`](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke)

Сигнатура метода:

```php 
\Kaspi\DiContainer\Interfaces\DiContainerCallInterface::call(
    array|callable|string $definition,
     mixed ...$argument
)
``` 

Параметры:
- `$definition` - вызываемый тип или значение преобразуемое к `callable` типу.
- `$argument` - аргументы для подстановки в параметры вызываемого типа.

> [!NOTE]
> Передавать аргументы для параметров вызываемого типа нужно только если они не могут быть внедрены автоматически на основании конфигурации контейнера.

## PHP класс преобразуемый в `callable` тип { #example-php-class }

PHP классы:

::: code-group

```php [PostController.php]
// file: /app/src/Controllers/PostController.php
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

```php [ServiceOne.php]
// file: /app/src/Services/ServiceOne.php
namespace App\Services;

final class ServiceOne
{
    // …
    
    public function save(string $name): void
    {
      // …
    }
}
```

:::

Контейнер зависимостей:

```php
use App\Controllers\PostController;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

/*
 * Вызов контроллера с автоматическим внедрением зависимостей
 * и передачей аргументов
 */
print $container->call(
    [PostController::class, 'store'],
    // $_POST содержит ['name' => 'Ivan']
    // 'name' соответствует имени аргумента в методе store
    ...\array_filter($_POST,  static fn ($v, $k) => 'name' === $k, \ARRAY_FILTER_USE_BOTH)
);

// The name 'Ivan' saved!
```

Фактически метод `call()` выполнит создание экземпляра класса `\App\Controllers\PostController` с внедрением зависимостей в конструктор и вызовет метод `\App\Controllers\PostController::store()`:

```php
// будет выполнено
(new \App\Controllers\PostController(serviceOne: new ServiceOne()))
    ->post(name: 'Ivan')
```

## PHP класс реализующий метод `__invoke()` преобразуемый в `callable` тип { #example-php-class-invoke }

PHP классы:

::: code-group

```php [SavePostController.php]
// file: /app/src/Controllers/SavePostController.php
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

```php [ServiceOne.php]
// file: /app/src/Services/ServiceOne.php
namespace App\Services;

final class ServiceOne
{
    // …
    
    public function save(string $name): void
    {
      // …
    }
}
```

:::

Контейнер зависимостей:

```php
use App\Controllers\SavePostController;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

print $container->call(SavePostController::class, name: 'Ivan');

// The name 'Ivan' saved!
```

Код выше эквивалентен этому код:

```php
$savePost = $container->get(SavePostController::class);
print $container->call([$savePost, '__invoke'], name: 'Ivan');
```

## Функция { #example-function }

::: code-group

```php [functions.php]
// file: /app/src/Functions/functions.php
namespace App\Functions;

use App\Services\ServiceOne;
use function var_export;

function one_service(ServiceOne $service, string $name) {
        $service->save($name);

        return sprintf('The name %s saved!', var_export($name, true));
};
```


```php [ServiceOne.php]
// file: /app/src/Services/ServiceOne.php
namespace App\Services;

final class ServiceOne
{
    // …
    
    public function save(string $name): void
    {
      // …
    }
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

// вызов функции и подстановкой именованного аргумента
print $container->call('\App\Functions\one_service', name: 'Ivan'); 

// The name 'Ivan' saved!
```

Фактически метод `call()` выполнит следующий код:

```php
\App\Functions\one_service(
    new App\Service\ServiceOne(),
    name: 'Ivan',
);
```

<!--@include: ../_include/term_notes.md-->
