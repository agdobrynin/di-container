# Документация

## Описание конфигурирования и использования
* 👷‍♂️ [Инструмент для сборки контейнера зависимостей **DiContainerBuilder**](00-container-builder.md).
* ⚙️ [Конфигурация для DiContainer](01-container-config.md).
* 🐘 [DiContainer с конфигурированием **в стиле php определений**](03-php-definition.md).
* #️⃣ [DiContainer c конфигурированием **через PHP атрибуты**](02-attribute-definition.md).
* 📦 [Метод контейнера `call()`](04-call-method.md) для вызова чистых `callable` типов и дополнительных определений.
* 🔖 [Тэгирование определений и сервисов](05-tags.md).
* 📋 [Параметры контейнера](09-container-parameters.md).
* 🗳️ [Внедрение экземпляра класса в рантайм контейнер](10-runtime-definition.md).
* 🧹 [Сброс контейнера зависимостей](11-container-reset.md).
* ♻️ [Сброс состояния объектов для долго-живущих процессов](12-object-resetters.md).
* 💤 [Внедрение «ленивых» объектов контейнером (lazy injection)](14-lazy-injection.md)

## Особенности разрешения некоторых классов и интерфейсов.

Некоторые интерфейсы или классы всегда возвращают текущий контейнер зависимостей.
При разрешении зависимости для интерфейсов и классов:
- `Psr\Container\ContainerInterface::class`
- `Kaspi\DiContainer\Interfaces\DiContainerInterface::class`
- `Kaspi\DiContainer\DiContainer::class`

будет получен текущий контейнер зависимостей.

```php
use Kaspi\DiContainer\DiContainerBuilder;
use Psr\Container\ContainerInterface;

function testFunc(ContainerInterface $c) {
    return $c;
}

$container = (new DiContainerBuilder())->build();

var_dump($container->call('testFunc') instanceof DiContainer); // true
var_dump($container->call('testFunc') instanceof ContainerInterface); // true
```

```php
use Kaspi\DiContainer\DiContainerBuilder;
use Psr\Container\ContainerInterface;

class TestClass {
    public function __construct(
        public ContainerInterface $container
    ) {}
}

$container = (new DiContainerBuilder())->build();

var_dump($container->get(TestClass::class)->container instanceof ContainerInterface); // true
```
