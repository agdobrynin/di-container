# Вызов через метод `call`:
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

class  Post {
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
    ['App\Controllers\Post', 'store'],
    [$_POST]
    // $_POST содержит ['name' => 'Ivan']
    // 'name' соответствует имени аргумента в методе store
);
```
результат
`The name Ivan saved!`

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
