# 🏭 Использование фабричных методов для разрешения зависимостей.

В некоторых сценариях возникает необходимость для разрешения зависимостей применить шаблон проектирования «фабрика»
чтобы делегировать процесс разрешения специальному объекту – «фабрика». 
В таких случаях контейнер может вызвать метод вашей фабрики для разрешения зависимости.

> [!TIP]
> Параметры конструктора и метода фабрики могут быть разрешены автоматически или на основе конфигурации контейнера.
> Для особых сценариев настройки фабрики можно [установить
> аргументы для метода-фабрики](#аргументы-для-метода-фабрики).

При использовании фабрики для разрешения зависимостей контейнера
выбранное значение для класса не влияет на получаемый результат выполнения фабричного метода.
Фактическое имя класса зависит только от объекта (определения), возвращаемого фабрикой.
Однако сконфигурированное имя класса может использоваться для более сложной настройки
класса фабрики, поэтому его следует установить на разумное значение.

Использование фабрик для разрешения зависимостей доступно как для php классов,
так и [для параметров методов (функций)](#фабрики-для-параметров-метода).

## Фабрика со статическим методом.
Для фабрики первым аргументом указывают имя класса,
вторым статический метода класса.

```php
// Фабрика
namespace App\Factories;

use App\Classes\Foo;

class ClassFactory
{
    public static function  create(): Foo
    {
        $createdObject = new Foo();
        // дополнительные настройки объекта

        return $createdObject;
    }
}
```
При конфигурировании через хэлпер функцию:
```php
// 
use App\Classes\Foo;
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\diFactory;

return static function () {
    yield Foo::class => diFactory([ClassFactory::class, 'create']);
};
```
При конфигурировании через php атрибут:
```php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\DiFactory;
use App\Factories\ClassFactory;

#[DiFactory([ClassFactory::class, 'create'])]
class Foo{}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$container->get(\App\Classes\Foo::class);
```
> [!NOTE]
> Класс `\App\Classes\Foo` будет создан через вызов `\App\Factories\ClassFactory::create()`


## Фабрика с нестатическим методом.
Если ваша фабрика использует обычный метод вместо статического,
первым шагом необходимо убедиться что php класс самой фабрики также был сконфигурирован
и доступен в контейнере как определение, т.е. может быть получен через
метод контейнера `get(\App\Factories\ClassFactory::class)`.

```php
namespace App\Factories;

use App\Classes\Foo;

class ClassFactory
{
    public function __construct(private Bar $bar) {}

    public function create(): Foo
    {
        $foo = new Foo($this->bar);
        // дополнительное конфигурирование `$foo`
        
        return $foo;
    }
}
```
Конфигурация класса фабрики в контейнере если недоступно [автоматическое разрешение зависимостей](../README.md#нулевая-конфигурация-для-внедрения-зависимостей):
```php
// config/services.php
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\diAutowire;

return function () {
    // доступен через метод контейнера `get(\App\Factories\ClassFactory::class)`
    yield diAutowire(ClassFactory::class)
};
``` 
Объявление определения фабрики через хэлпер функцию:
```php
// config/services_base.php
use App\Classes\Foo;
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\diFactory;

return static function () {
    yield Foo::class => diFactory([ClassFactory::class, 'create']);
};
```
Объявление фабрики через php атрибут:
```php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\DiFactory;
use App\Factories\ClassFactory;

#[DiFactory([ClassFactory::class, 'create'])]
class Foo{}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->load(__DIR__.'/config/services_base.php')
    ->build()
;

$container->get(\App\Classes\Foo::class);
```
> [!NOTE]
> Класс \App\Classes\Foo будет создан через вызов `\App\Factories\ClassFactory::create()`.
> 
> При разрешении фабрики, контейнер попытается разрешить зависимость
> `ClassFactory::$bar` в конструкторе на основе конфигурации.
>

#### Класс фабрики как идентификатор контейнера.
Фактическое имя класса фабрики зависит только от объекта (определения), получаемого от контейнера.
В некоторых сценариях использования можно указывать идентификатор контейнера в качестве
имени класса фабрики при условии, что определение (_definition_) по указанному идентификатору,
реализует указанный фабричный метод.

```php
namespace App\Factories;

use App\Classes\Foo;
use Psr\Log\LoggerInterface;

class ClassFactory
{
    private LoggerInterface $logger;

    public function __construct(private Bar $bar) {}
    
    public function withLogger(LoggerInterface $logger): self
    {
        $new = clone $this;
        $new->logger = $logger;
        
        return $new;
    }

    public function create(): Foo
    {
        $foo = new Foo($this->bar);
        // дополнительное конфигурирование `$foo`
        if (isset($this->logger)) {
            $this->logger->info('Configured class via factory', $foo);
        }
        
        return $foo;
    }
}
```

```php
// config/services.php
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\{diAutowire, diGet};

return function () {
    
    // доступен через метод контейнера `get('factories.class_factory.config_one')`
    yield 'factories.class_factory.config_one' => diAutowire(ClassFactory::class)
        ->setupImmutable('withLogger', diGet('services.app_logger'));

    // использование идентификатора контейнера
    yield Foo::class => diFactory([
        'factories.class_factory.config_one', 'create'
    ]);

};
```
Объявление фабрики через php атрибут:
```php
use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(['factories.class_factory.config_one', 'create'])]
class Foo{}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$container->get(\App\Classes\Foo::class);
```
> [!NOTE]
> Класс `\App\Classes\Foo` будет создан через вызов
> метода контейнера `$object = $container->get('factories.class_factory.config_one')` 
> и исполнением метода `$object::create()`.
>
> При разрешении фабрики, контейнер попытается разрешить зависимость
> `ClassFactory::$bar` в конструкторе на основе конфигурации.
>
### Фабрика с методом `__invoke()`.
В качестве фабрики может быть использован метод `__invoke()`.
Синтаксис объявления определения может быть сокращён до указания имени класса в виде строки (fully qualified class name).
```php
namespace App\Factories;

use App\Classes\Foo;

class ClassFactoryInvokable
{
    public function  __invoke(): Foo
    {
        $foo = new Foo();
        // дополнительное конфигурирование `$foo`
        
        return $foo;
    }
}
```
Объявление определения фабрики через хэлпер функцию:
```php
// config/services.php
use App\Classes\Foo;
use App\Factories\ClassFactoryInvokable;
use function Kaspi\DiContainer\diFactory;

return function () {
    yield Foo::class => diFactory(ClassFactoryInvokable::class);
};
```
Объявление фабрики через php атрибут:
```php
use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(\App\Factories\ClassFactoryInvokable::class)]
class Foo{}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$container->get(\App\Classes\Foo::class);
```
> [!NOTE]
> Класс `\App\Classes\Foo` будет создан через вызов
> метода контейнера `$object = $container->get(App\Factories\ClassFactoryInvokable::class)`
> и исполнением метода `$object::__invoke()`.
> 

## Аргументы для метода фабрики.

Для особых сценариев настройки фабрики можно установить аргументы для параметров метода-фабрики:

- Через метод в хэлпер функции `\Kaspi\DiContainer\diFactory::bingArguments()`:
    ```php
    bindArguments(mixed ...$argument)
    ``` 
  > для передачи неполного списка аргументов используйте именованные аргументы.

- Через параметр `$arguments` у php атрибута `\Kaspi\DiContainer\Attributes\DiFactory` :
    ```php
    #[DiFactory(string|array $definition, ?bool $isSingleton = null, array $arguments = [])]
    ```
  > для передачи неполного списка аргументов используйте в качестве ключа в массиве `$arguments` имя параметра в методе фабрике.

> [!TIP]
> Для указания как разрешать скалярные типы зависимостей в аргументах рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».


> [!TIP]
> Для параметров **не объявленных** через `bindArgument()` или через `$arguments` в php атрибуте `\Kaspi\DiContainer\Attributes\DiFactory`,
> контейнер попытается разрешить зависимости самостоятельно на основе конфигурации контейнера.

> [!TIP]
> Для передачи аргументов при конфигурировании контейнера [в стеле PHP определений](01-php-definition.md) можно использовать хэлпер функции такие как:
> - `\Kaspi\DiContainer\diGet`
> - `\Kaspi\DiContainer\diParameter`
> - `\Kaspi\DiContainer\diAutowire`
> - `\Kaspi\DiContainer\diTaggedAs`
> - `\Kaspi\DiContainer\diProxyClosure`
> - `\Kaspi\DiContainer\diCallable`
> - `\Kaspi\DiContainer\diValue`
> 
> 
> Для php атрибута `\Kaspi\DiContainer\Attributes\DiFactory` использовать доступные определения такие как:
> - `Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire` – php класс
> - `Kaspi\DiContainer\DiDefinition\DiDefinitionGet` – ссылка на идентификатор контейнера
> - `Kaspi\DiContainer\DiDefinition\DiDefinitionCallable` – `callable` тип
> - `Kaspi\DiContainer\DiDefinition\DiDefinitionValue` – определение «как есть».
> - `Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure` – сервис через вызов `\Closure`
> - `Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs` – тегированные определения
> - `Kaspi\DiContainer\DiDefinition\DiDefinitionParameter` – параметр контейнера
>

### Пример передачи аргументов в метод фабрику через параметры контейнера.

```php
namespace App\Factories;

use App\Services\Bar;

final class ClassFactory {

    public static function create(
        string $var1,
        string $var2,
        Bar $bar,
    ) {}

}
```

**В хелпер функцию:**

```php
use App\Services\Foo;
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\{diFactory, diParameter};

return static function () {
    yield Foo::class => diFactory([ClassFactory::class, 'create'])
        // передача в параметр #1
        // передача к параметру `$var2`
        // для параметра `$bar` выполнить разрешение на основе настроек контейнера 
        ->bindArguments(
            diParameter('app.param1'),
            var2: diParameter('app.param2'),
        );

}
```
**В php атрибут:**

```php
namespace App\Services;

use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;

#[DiFactory(
    [ClassFactory::class, 'create'],
    // передача в параметр #1
    // передача к параметру `$var2`
    // для параметра `$bar` выполнить разрешение на основе настроек контейнера 
    arguments: [
        new DiParameter('app.param1'),
        'var2' => DiParameter('app.param2'),
    ]
)]
final class Foo {}
```

> [!NOTE]
> Подробное описание работы с «[параметрами контейнера](09-container-parameters.md)».

### Указание аргумента через другие определения контейнера.
Для сценариев когда необходимо указать аргумент через определение можно использовать хэлпер функции и классы определений контейнера.
```php
namespace App\Factories;

use App\Services\Bar;

final class ClassFactory {

    public static function create(
        Bar $bar,
    ) {}

}
```
**В хэлпер функции:**
```php
use App\Services\Foo;
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\{diFactory, diGet};

return static function () {
    yield Foo::class => diFactory([ClassFactory::class, 'create'])
        // получить значение через метод контейнера `get('services.bar')`
        ->bindArguments(bar: diGet('services.bar'));

}
```
**В php атрибут:**
```php
namespace App\Services;

use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;

#[DiFactory(
    [ClassFactory::class, 'create'],
    // для параметра `$bar` получить значение
    // через метод контейнера `get('services.bar')`
    arguments: [
        'bar' => new DiGet('services.bar'),
    ]
)]
final class Foo {}
```

## Фабрики для параметров метода.
С помощью фабрики также доступно разрешение зависимостей для параметров метода (функции).
```php
namespace App\Factories;

use use App\Interfaces\ApiClientInterface;

final class ApiClientFactory
{
    public static function createApiV2(): ApiClientInterface
    {
        //..
        return $object;
    }
}
```
В хелпер функции:
```php
// src/config/services.php

use App\Classes\Baz;
use App\Factories\ApiClientFactory;
use function Kaspi\DiContainer\{diAutowire, diFactory};

return static function (): \Generator {

    yield diAutowire(Baz::class)
        ->bindArguments(
            apiClient: diFactory(
                [ApiClientFactory::class, 'createApiV2']
            )
        );

};
```

В php атрибуте:
```php
namespace App\Classes;

use App\Interfaces\ApiClientInterface;
use App\Factories\ApiClientFactory;
use Kaspi\DiContainer\Attributes\DiFactory;


class Baz {

    public function __construct(
        #[DiFactory([ApiClientFactory::class, 'createApiV2'])]
        private readonly ApiClientInterface $apiClient
    ) {}

}
```
