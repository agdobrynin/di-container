---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
isHome: true

hero:
  name: Kaspi/DiContainer
  text: Контейнер внедрения зависимостей для PHP
  tagline: Легковесная кодовая база без внешних зависимостей и лишнего «мусора».
  image:
    src: /logo2.svg
    alt: kaspi/di-container
features:
  - title: Autowire
    details: Автоматическое разрешение зависимостей для конструктора класса или для параметров вызываемого тип.
    icon: 🛠️
  - title: Lazy injection
    details: Отложенная инициализация внедряемых объектов.
    icon: 💤
  - title: Attribute-driven
    details: Настройка без необходимости конфигурации, реализованная на базе нативных атрибутов PHP 8+.
    icon:  #️⃣ 
  - title: Zero configuration
    details: Если класс не имеет зависимостей или зависит только от других конкретных классов, контейнеру не нужно указывать, как разрешить этот класс.
    icon: 🧊
  - title: Поддержка тегов
    details: Получение коллекции определений и сервисов в контейнере по тегу.
    icon: 🏷️
  - title: Компиляция контейнера
    details: Генерация настроенного контейнера в PHP-код оптимизированный специально для вашей конфигурации и ваших классов.
    icon: 📦

---

## Установка

```shell
composer require kaspi/di-container
```

## Быстрый старт


```php
use App\Services\{Foo, Bar};
use Kaspi\DiContainer\DiContainerBuilder;

// Создать контейнер
$container = (new DiContainerBuilder())
    ->import('App\\', src: '/var/www/app/src')
    ->build();

// Получить класс с внедренными зависимостями в стиле PSR-11
if ($container->has(Foo::class)) {
    $foo = $container->get(Foo::class);
}

// Получить результат вызова метода `\App\Services\Bar::methodName()`
$callResult = $container->call([Bar::class, 'methodName'], ['param' => 'qux']);
```
