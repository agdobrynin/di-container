# Autowire

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Autowire` позволяет конфигурировать PHP класс как определение для контейнера и может применяться к PHP классу или к параметру метода (функции).

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Autowire::__construct(
    string $id = '',
    ?bool $isSingleton = null,
    array $arguments = [],
    array|\Kaspi\DiContainer\Attributes\Tag|null $tags = null,
    ?array $setups = null,
    callable|false|string $resetter = false,
    bool $isLazy = false,
)
```

Параметры:

- `$id` – идентификатор контейнера для класса (_container identifier_).
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$arguments` – предать аргументы для конструктора PHP класса.
- `$tags` – указание тегов к конкретному идентификатору контейнера указанному в параметре `$id`.
- `$setups` – указание сеттер методов PHP класса для настройки PHP класса к конкретному идентификатору контейнера указанному в параметре `$id`.
- `$resetter` – значение которое будет вызвано [для сброса состояния объекта](../12-object-resetters.md).
- `$isLazy` – обозначение определения как «ленивый объект». Подробнее в разделе – [Внедрение «ленивых» объектов контейнером](../14-lazy-injection.md).

## Идентификатор контейнера { #container-id }

**Для атрибута примененного к PHP классу** пустая строка в `\Kaspi\DiContainer\Attributes\Autowire::$id` будет интерпретирована контейнером как полное имя класса (_Fully Qualified Class Name_):

```php
// /app/src/Services/FooService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(arguments: [])]
/**
 * 🚩 Эквивалентно объявлению
 * #[Autowire(id: FooService::class, arguments: [])] 
 */
final class FooService
{
    // …
}
```

## Внедрение зависимостей через конструктор PHP класса { #arguments }

## Внедрение зависимостей через сеттер-методы { #setups }

## Указание тегов { #tags }

## Применение `Autowire` к параметру метода { #autowire-on-param }