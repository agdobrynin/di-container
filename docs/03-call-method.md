# 📦 DiContainer::call

Контейнер предоставляет метод `call()`, который может вызывать любой PHP **callable** тип
или [преобразуемый в callable тип](#класс-с-нестатическим-методом-).

#### Поддерживаемые типы:
- Функция `is_callable`
  ```php
    function userFunc() { /*... do something ... */ }
    // ...
    $container->call('userFunc');
  ```
- Callback функция (`\Closure`) `is_callable`
    ```php
    $container->call(static function() { /*... do something ... */ });
    ```
- Статические методы класса `is_callable`
  ```php
  $container->call('App\MyClass::someStaticMethod');
  $container->call(App\MyClass::class.'::someStaticMethod');
  ```
- Метод у созданного класса `is_callable`
  ```php
  $container->call([$classInstance, 'someMethod']);
  ```
- Класс реализующий метод __invoke() `is_callable`
  ```php
  $container->call($classInstance);
  ```
#### Класс с нестатическим методом (*)

- поддерживаемые преобразования в callable Тип
  ```php
  $container->call(App\MyClass::class); // исполнение метода __invoke
  $container->call([App\MyClass::class, 'someMethod']);
  $container->call(App\MyClass::class.'::someMethod');
  $container->call('App\MyClass::someMethod');
  ```

> (*) при вызове будет создан экземпляр класса `App\MyClass::class` с разрешением
> зависимостей в конструкторе класса и затем будет исполнен указанный метод. Если метод
> не указан, то будет попытка вызвать метод `__invoke` 


Аргументы метода:
```php
call(array|callable|string $definition, array $arguments = [])
```

Метод может:
- принимать аргументы по имени для подстановки в callable функцию. Ключ в `$arguements` должно соответствовать имени аргумента в вызываемом методе/функции.
- внедрять зависимости через автоматическое разрешение зависимостей (autowire) при вызове

Абстрактный пример с контроллером:
```php
// определение класса
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
namespace App;

use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();
```
```php
// вызов контроллера с автоматическим разрешением зависимостей и передачей аргументов
print $container->call(
    ['App\Controllers\PostController', 'store'],
    // $_POST содержит ['name' => 'Ivan']
    // 'name' соответствует имени аргумента в методе store
    \array_filter($_POST,  static fn ($v, $k) => 'name' === $k, \ARRAY_FILTER_USE_BOTH)
);
```
результат
`The name Ivan saved!`

Фактически `call` выполнит создание экземпляра класс `App\Controllers\PostController` с внедрением зависимостей в конструктор
и вызовет метод `App\Controllers\PostController::store`

```php
// будет выполнено
(new App\Controllers\PostController(serviceOne: new ServiceOne()))
    ->post(name: 'Ivan')
```

Абстрактный пример с `autowiring` и подстановкой дополнительных параметров при вызове функции:

```php
use Kaspi\DiContainer\DiContainerFactory;
// определение контейнера
$container = (new DiContainerFactory())->make();

// ... more code ...

// определение callback с типизированным параметром
$helperOne = static function(App\Service\ServiceOne $service, string $name) {
        $service->save($name);
        
        return 'The name '.$name.' saved!';
};

// ... more code ...

// вызов callback с autowiring
print $container->call($helperOne, ['name' => 'Vasiliy']); // The name Vasiliy saved! 
```


```php
// будет выполнено
$helperOne(
    new App\Service\ServiceOne(),
    'Vasiliy'
);
```
