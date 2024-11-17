# DiContainer::call

Контейнер предоставляет метод `call()`, который может вызывать любой PHP **callable** тип:

- функция
- callback функция (`\Closure`)
- статические методы класса - `App\MyClass::someStaticMethod`
- метод у созданного класса - `[$classInstance, 'someMethod']`
- класс реализующий метод __invoke() - `$classInstance`

#### 🔢 Так же доступны вызовы с параметрами:

- Класс реализующий `__invoke` метод
```php
$container->call(App\MyClassWithInvokeMethod::class);
```
- класс с нестатическим методом (*)
```php
$container->call([App\MyClass::class, 'someMethod']);
$container->call(App\MyClass::class.'::someMethod');
$container->call('App\MyClass::someMethod');
```

> (*) при вызове если `App\MyClass` нуждается в создании
> через конструктор то он будет создан используя `DiContainer::get(App\MyClass::class)`

# Вызов `callable` типов через метод `call`:

Метод может:
- принимать агрументы по имени для подстановки в callable вызов. Имя ключа в `$arguements` должно соотвесторовать имени аргумента в вызываемом методе/функции.
- внедрять зависимости через автоматическое разрешение зависимостей (autowire) при вызове

Аргументы метода:
```php
call(array|callable|string $definition, array $arguments = [])
```
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
    [$_POST]
    // $_POST содержит ['name' => 'Ivan']
    // 'name' соответствует имени аргумента в методе store
);
```
результат
`The name Ivan saved!`

Фактически `call` выполнит создание экземпляра класс `App\Controllers\PostController` с внедрением зависимостей в конструктор класса,
выполнит метод `App\Controllers\PostController::store`

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
