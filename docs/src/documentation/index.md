# Руководство { #overview }

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
* 📦 [Метод контейнера `call()`](call/index.md) для вызова `callable` типов и выражений преобразуемых к `callable` типу.
* 🔖 [Тэгирование определений и сервисов](05-tags.md).
* 📋 [Параметры контейнера](09-container-parameters.md).
* 🗳️ [Внедрение экземпляра класса в рантайм контейнер](10-runtime-definition.md).
* 🧹 [Сброс контейнера зависимостей](11-container-reset.md).
* ♻️ [Сброс состояния объектов для долго-живущих процессов](12-object-resetters.md).
* 💤 [Внедрение «ленивых» объектов контейнером (lazy injection)](14-lazy-injection.md)

