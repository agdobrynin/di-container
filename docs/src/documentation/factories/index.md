# Использование фабричных методов для внедрения зависимостей { #title }

## Обзор { #overview }

В некоторых сценариях возникает необходимость для внедрения зависимостей применить шаблон проектирования «фабрика» чтобы делегировать процесс создания требуемого объекта специальному PHP-классу – «фабрика». В таких случаях контейнер может вызвать метод вашего PHP-класса для внедрения зависимости.

Параметры конструктора и метода PHP-класса «фабрика» могут быть внедрены автоматически на основании конфигурации контейнера. Для особых сценариев можно [объявить аргументы для метода фабрики](args.md).

При объявлении внедрения зависимости через фабрику выбранное значение для класса не влияет на получаемый результат выполнения фабричного метода. [Фактическое имя класса фабрики](factory_class_container_id.md) зависит только от объекта получаемого через метод контейнера `get()`. Однако разумное значение для имени класса фабрики может упростить его использование при конфигурировании контейнера.

**В качестве PHP-класса «фабрика» можно использовать:**
- Публичный статический метод PHP-класса.
- Публичный метод PHP-класса.
- Магический метод `__invoke()` реализованный в PHP-классе.

PHP-класс «фабрика» можно использовать для [объявления внедрения в параметры](inject_into_params.md) методов или вызываемых типов[^CallableType].

Для случаев когда объявление PHP-класса не может быть создано простым вызовом `new Foo()`, а требует особой сборки, то нужно [делегировать сборку этого класса специальному PHP-классу – «фабрика»](declare_class.md).

Объявить PHP-класс «фабрика» можно через:
- [хелпер функцию `diFactory()`](../php-definition/di-factory.md) в файлах конфигураций.
- [атрибут `\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md).

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
При конфигурировании через хелпер функцию:
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
первым шагом необходимо убедиться что PHP-класс самой фабрики также был сконфигурирован
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
Конфигурация класса фабрики в контейнере если недоступно [автоматическое разрешение зависимостей](01-container-config.md#нулевая-конфигурация-для-внедрения-зависимостей):
```php
// config/services.php
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\diAutowire;

return function () {
    // доступен через метод контейнера `get(\App\Factories\ClassFactory::class)`
    yield diAutowire(ClassFactory::class)
};
``` 
Объявление определения фабрики через хелпер функцию:
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


## Фабрика с методом `__invoke()`.
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
Объявление определения фабрики через хелпер функцию:
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




<!--@include: ../_include/term_notes.md-->