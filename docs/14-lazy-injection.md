# 💤 Внедрение «ленивых» объектов контейнером (lazy injection)
> [!IMPORTANT]
> Механизм внедрения ленивых зависимостей доступен только для PHP 8.4 и выше.

Ленивое внедрение зависимостей позволяет отложить создание зависимостей объекта
до момента их фактического использования, а не раньше, для реализации этого
функционала контейнера используется [«Virtual Proxies» описанный в PHP документации](https://www.php.net/manual/ru/language.oop5.lazy-objects.php).

## Как использовать

Определение контейнера создаваемое через [хелпер функцию `diAutowire()`](01-php-definition.md#diautowire) 
или PHP класс сконфигурированный через [атрибут `Autowire()`](02-attribute-definition.md#autowire) можно определить как «ленивый объект».
Если определение контейнера внедряется как зависимость, то вместо него будет внедрен прокси-объект реализующий «отложенную» инициализацию объекта.

```php
// Сервис с требующий ленивой инициализации
namespace App\Services;

final class Foo {
    public function __construct(private HeavyDependency $heavyDependency) {}
    
    public function doHeavy(): mixed
    {
        $result = $this->dependency->calculate();
        //...
        return $result;
    }
}
```
```php
namespace App\Services;

// Класс использующий `App\Services\Foo`
final class Bar {
    /*
     * В момент вызова метода `__construct()`
     * сервис $foo ещё не инициализирован, его инициализация
     * потребуется только в момент обращения к `$this->foo`.
     * 
     * Сервис $baz инициализируется в момент вызова метода `__construct()`.
     */ 
    public function __construct(private Foo $foo, private Baz $baz) {}
    //
    public function doFoo()
    {
        // Вызвать инициализацию сервиса `App\Services\Foo`
        $result = $this->foo->doHeavy();    
    }
}
```

**⚠️ Необходимо правильно сконфигурировать `App\Services\Foo` чтобы внедрение зависимости было организовано как «ленивый объект».**

🐘 Пример конфигурирования через хелпер функцию в файле конфигурации определений:
```php
// src/config/services_lazy.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {
    // Объект будет внедряться как «ленивый» – указываем параметр $isLazy = true
    yield diAutowire(Foo::class, isLazy: true);    
};

```
#️⃣ Пример конфигурирования `App\Services\Foo` через php атрибут:

```php
// Сервис с требующий ленивой инициализации
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isLazy: true)]
final class Foo {
    public function __construct(private HeavyDependency $dependency) {}
    // ...
}

```
После сборки контейнера:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->build();

/*
 * Получение сервиса в котором свойство `\App\Services\Bar::$foo` еще не инициализировано,
 * занимая очень мало памяти (ресурсов).
 */
$bar = $container->get(\App\Services\Bar::class);
/*
 * Вызов инициализации объекта `\App\Services\Foo`
 * через «Virtual Proxy» в свойство `\App\Services\Bar::$foo`.
 */
$result = $bar->doFoo();
/*
 * Теперь в свойстве `\App\Services\Bar::$foo` создан реальный объект `\App\Services\Foo`.
 */
```

> [!TIP]
> Если версия PHP ниже 8.4, то можно использовать варианты внедрения зависимостей через `\Closure` класс:
> - [хелпер функция `diProxyClosure()`](01-php-definition.md#diproxyclosure)
> - [PHP атрибут `ProxyClosure()`](02-attribute-definition.md#proxyclosure)
> 