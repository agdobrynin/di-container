---
outline: [2, 4]
---
# Руководство { #documentation }

## Обзор { #overview }

В этой документации описывается API контейнера из пакета [kaspi/di-container](https://packagist.org/packages/kaspi/di-container).

### PSR-11

Контейнер полностью совместим с рекомендациями [PSR-11](https://www.php-fig.org/psr/psr-11/):

```php
namespace Psr\Container;

interface ContainerInterface
{
    public function get($id);
 
    public function has($id);
}
```
### `call()`

Контейнер реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface`
который предоставляет метод `call()` для вызываемых типов или значений которые могут быть преобразованы контейнером к `callable` типу.

```php
use App\Services\Foo;
use Kaspi\DiContainer\DiContainerBuilder;

use function var_export;

$container = (new DiContainerBuilder())->build();

$container->call(
    static function(Foo $foo, string $name): void {
        $foo->doSomethind($name);
        sprintf('Call Foo::doSomething(%s)', var_export($name, true));
    },
    name: 'John'
);
// string(29) "Call Foo::doSomething('John')"
```

Подробнее о методе `call()` рассказано в разделе [«Метод контейнера для вызываемых типов»](call/index.md).

### set()

Контейнер реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface` который предоставляет метод `set()`
для прямой установки объекта (инстанцированный класс) в уже сформированный контейнер зависимостей.

```php
use App\Services\Bar;

$bar = new Bar(
    // set some dependencies.
);

// идентификатор будет указан как 'App\Services\Bar'
$container->set($bar::class, $bar);
```

Подробнее о методе `set()` рассказано в разделе [«Динамическое добавление определений в контейнер»](container-builder/set.md).

## Особенности разрешения некоторых классов и интерфейсов { #resolve-class-interface }

При разрешении зависимостей ниже перечисленных типов всегда будет получен созданный контейнер:
- `\Psr\Container\ContainerInterface`
- `\Kaspi\DiContainer\Interfaces\DiContainerInterface`
- `\Kaspi\DiContainer\DiContainer`

Для свойства PHP класса:
```php
use Kaspi\DiContainer\DiContainerBuilder;
use Psr\Container\ContainerInterface;

class Foo {
    public function __construct(
        public readonly ContainerInterface $container
    ) {}
}

$container = (new DiContainerBuilder())->build();

var_dump($container === $container->get(Foo::class)->container);
// (bool) true
```

## Содержание { #toc }

* 👷‍♂️ [Инструмент для сборки контейнера зависимостей **DiContainerBuilder**](container-builder/index.md).
* ⚙️ [Конфигурация для DiContainer](container-config/index.md).
* 🐘 [DiContainer с конфигурированием **в стиле php определений**](php-definition/index.md).
* #️⃣ [DiContainer c конфигурированием **через PHP атрибуты**](attribute-definition/index.md).
* 📦 [Метод контейнера `call()`](call/index.md).
* 🔖 [Тэгирование определений и сервисов](05-tags.md).
* 📋 [Параметры контейнера](09-container-parameters.md).
* 🗳️ [Внедрение экземпляра класса в рантайм контейнер](10-runtime-definition.md).
* 🧹 [Сброс контейнера зависимостей](11-container-reset.md).
* ♻️ [Сброс состояния объектов для долго-живущих процессов](12-object-resetters.md).
* 💤 [Внедрение «ленивых» объектов контейнером (lazy injection)](14-lazy-injection.md)

