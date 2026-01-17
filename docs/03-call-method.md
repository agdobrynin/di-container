# 📦 DiContainer::call()

Контейнер предоставляет `DiContainer::call()`, который может вызывать любой PHP **callable** тип
или [преобразуемый в callable тип](#класс-с-нестатическим-методом-).

#### Поддерживаемые типы:
- Функция
  ```php
    function userFunc() { /*... do something ... */ }
    // ...
    $container->call('userFunc');
  ```
- Callback функция `\Closure`
    ```php
    $container->call(static function() { /*... do something ... */ });
    ```
- Статические методы класса
  ```php
  $container->call('App\MyClass::someStaticMethod');
  $container->call(App\MyClass::class.'::someStaticMethod');
  $container->call([App\MyClass::class, 'someStaticMethod']);
  ```
- Нестатический метод PHP класса [*](#класс-с-нестатическим-методом-) (_преобразование контейнером к callable типу_)
  ```php
  $container->call([App\MyClass::class, 'someMethod']);
  ```
- Нестатический метод `__invoke()` PHP класса [*](#класс-с-нестатическим-методом-) (_преобразование контейнером к callable типу_)
  ```php
  $container->call(App\MyClass::class);
  ```
#### Класс с нестатическим методом (*)

- поддерживаемые преобразования в `callable` тип, через создание нового объекта PHP класса с разрешением зависимостей в конструкторе и вызовом метода:
 ```php
  $container->call(App\MyClass::class);
  // будет преобразовано
  $callable = [new App\MyClass(), '__invoke'];
 ```  
 ```php
  $container->call([App\MyClass::class, 'someMethod']); 
  $container->call(App\MyClass::class.'::someMethod');
  $container->call('App\MyClass::someMethod');
  // будет преобразовано
  $callable = [new App\MyClass(), 'someMethod'];
 ```
## Метод контейнера `DiContainer::call()`
Получение результата `callable` типа или преобразуемого в `callable` выражения, с разрешением зависимостей через контейнер.
```php
call(array|callable|string $definition, array $arguments = [])
```
Аргументы:
- `$definition` - `callable` тип или значение преобразуемое к `callable`.
- `$arguments` - аргументы для подстановки в `callable` тип.

> [!TIP]
> Можно использовать именованные аргументы в `$arguments` для подстановки.

> [!TIP]
> Для аргументов не объявленных в `$arguments` контейнер попытается разрешить зависимости самостоятельно.

Абстрактный пример с контроллером:
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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

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

> [!NOTE]
> Фактически `DiContainer::call()` выполнит создание экземпляра класс `App\Controllers\PostController` с внедрением зависимостей в конструктор
> и вызовет метод `App\Controllers\PostController::store`
> ```php
> // будет выполнено
> (new App\Controllers\PostController(serviceOne: new ServiceOne()))
>    ->post(name: 'Ivan')
> ```

Абстрактный пример с автоматическим разрешением зависимостей
и подстановкой дополнительных параметров при вызове функции:

```php
use Kaspi\DiContainer\DiContainerBuilder;
// определение контейнера
$container = (new DiContainerBuilder())->build();

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
> [!NOTE]
> будет выполнено
> ```php
> $helperOne(
>     new App\Service\ServiceOne(),
>     'Vasiliy'
> );
> ```
