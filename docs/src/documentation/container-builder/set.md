# Динамическое объявление определений

## Обзор { #overview }

В некоторых сценариях использования контейнера невозможно объявить PHP класс или другое определение при сборке контейнера через [методы класса `DiContainerBuilder`](index.md#methods) так как его конфигурация заранее неизвестна. Реальное состояние объекта инициализируется вне контейнера, и устанавливается в контейнер только во время выполнения.

Метод контейнера `set()` описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface`.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface::set(
    string $id,
    mixed $definition
): static;
```

Параметры:
- `$id` – идентификатор контейнера [^ContainerId].
- `$definition` – объект.

> [!WARNING]
> Метод `set()` может выбросить исключение реализующее интерфейс `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierAlreadyRegisteredExceptionInterface` если идентификатор контейнера указанный в параметре `$id` не уникален в конфигурации контейнера. 

Важно заметить что динамически объявленные объекты не будут [скомпилированы](methods/compile-to-file.md).

**Для сохранения высокой производительности контейнера рекомендуется использовать объявление PHP классов через [хелпер функцию `diRuntime()`](../php-definition/di-runtime.md) или [атрибут `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md)**.

## Пример динамического объявления объекта в контейнере { #example }

```php
<?php
declare(strict_types=1);

// file: /app/src/container_builder.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use VendorFoo\Services\Bar;//[!code ++]

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

$object = new \VendorFoo\Services\Bar();

$container->set($object::class, $object);//[!code ++]

var_dump($container->get(Bar::class) instanceof Bar);
// bool(true)
```

<!--@include: ../_include/term_notes.md-->