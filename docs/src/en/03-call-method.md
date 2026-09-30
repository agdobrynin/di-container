# 📦 Метод `DiContainer::call()`.

Контейнер реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface`
предоставляющий метод `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface::call()`.

Получение результата вызываемого типа или [преобразуемого в callable тип](#класс-с-нестатическим-методом) значения, с разрешением зависимостей через контейнер:
```php
call(array|callable|string $definition, mixed ...$argument)
```
Параметры:
- `$definition` - вызываемый тип или значение преобразуемое к `callable`;
- `$argument` - аргументы для подстановки в параметры вызываемого типа;

> [!WARNING]
> Необходимо передать аргументы в `$argument` для параметров функции или метода которые **не могут быть разрешены контейнером автоматически**

#### Поддерживаемые типы:
- Функция
  ```php
    function userFunc(\App\Services\Bar $bar) { /*... do something ... */ }
    // ...
    $container->call('userFunc');
  ```
- анонимная функция через PHP класс `\Closure`
    ```php
    $container->call(static function() { /*... do something ... */ });
    ```
- Статические методы класса
  ```php
  namespace App\Services;
  
  class Foo {
    public static function bar(\App\Services\Bar $bar)
    {
        // ...
    }
  }
  ```
  ```php
  $container->call('\App\Services\Foo::bar');
  
  $container->call(\App\Services\Foo::class.'::bar');
  
  $container->call([\App\Services\Foo::class, 'bar']);
  ```
- Созданный объект PHP класса и метод класса
  ```php
  namespace App\Services;
  
  class Foo {
    public function __construct() {}

    public function qux(\App\Services\Bar $bar)
    {
        // ...
    }
  }
  ```
  ```php
  $object = new \App\Services\Foo();
  
  $container->call([$object, 'qux']);
  ```

#### Класс с нестатическим методом.

Поддерживаемые преобразования в вызываемый тип, через получение контейнером PHP класса
с разрешением зависимостей в конструкторе и вызовом указанного метода:

- PHP класс реализующий метод `__invoke()`:
  ```php
  namespace App\Services;
  
  class Foo {
    public function __construct() {}
    public function __invoke(\App\Services\Bar $bar) {}
  }
  ```
  ```php
  $container->call(\App\Services\Foo::class);
  ```
  метод `call()` выполнит следующие действия:
  ```php
    $object = new \App\Services\Foo();
    $object2 = new \App\Services\Bar();

    $object->__invoke($object2);
  ```

- PHP класс представленный через полное имя (fully qualified class name) и вызываемый метод:
  ```php
  namespace App\Services;
  
  class Foo {
    public function __construct() {}
    public function qux(\App\Services\Bar $bar) {}
  }
  ``` 
  ```php
  $container->call([\App\Services\Foo::class, 'qux']);
  
  $container->call(\App\Services\Foo::class.'::qux');
  
  $container->call('\App\Services\Foo::qux');
  ```
  метод `call()` выполнит следующие действия:
  ```php
    $object = new \App\Services\Foo();
    $object2 = new \App\Services\Bar();

    $object->qux($object2);
  ```

### Абстрактный пример с контроллером:
```php
// src/Controllers/PostController.php
namespace App\Controllers;

use App\Service\ServiceOne;

class  PostController {
    public function __construct(private ServiceOne $serviceOne) {}
    
    public function store(string $name) {
        $this->serviceOne->save($name);
        
        return 'The name '.$name.' saved!';
    }
}
```

```php
// определение контейнера
use App\Controllers\PostController;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

// вызов контроллера с автоматическим разрешением зависимостей и передачей аргументов
print $container->call(
    [PostController::class, 'store'],
    // $_POST содержит ['name' => 'Ivan']
    // 'name' соответствует имени аргумента в методе store
    ...\array_filter($_POST,  static fn ($v, $k) => 'name' === $k, \ARRAY_FILTER_USE_BOTH)
);
```
результат
`The name Ivan saved!`

> [!NOTE]
> Фактически метод `call()` выполнит создание экземпляра класс `\App\Controllers\PostController` с внедрением зависимостей в конструктор
> и вызовет метод `\App\Controllers\PostController::store()`
> ```php
> // будет выполнено
> (new \App\Controllers\PostController(serviceOne: new ServiceOne()))
>    ->post(name: 'Ivan')
> ```

### Абстрактный пример с функцией:
```php
namespace App\Functions;

function one_service(App\Service\ServiceOne $service, string $name) {
        $service->save($name);
        
        return 'The name '.$name.' saved!';
};
```
```php
// определение контейнера
$container = (new \Kaspi\DiContainer\DiContainerBuilder())
    ->build()
;

// вызов callback с autowiring и подстановкой именованного аргумента
print $container->call('\App\Functions\one_service', name: 'Vasiliy'); 
```
> [!NOTE]
> будет выполнено
> ```php
> \App\Functions\one_service(
>     new App\Service\ServiceOne(),
>     name: 'Vasiliy',
> );
> ```
