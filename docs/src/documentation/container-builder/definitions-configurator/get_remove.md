# Объявление и получение определений контейнера { #title }

## Обзор { #overview }

При сборке контейнера через `\Kaspi\DiContainer\DiContainerBuilder` все объявленные определения будут доступны в конфигураторе определений, включая импортированные определения через [метод `DiContainerBuilder::import()`](../index.md#dicontainerbuilder-import).

## `getDefinitions()`

Метод `getDefinitions()` возвращает коллекцию всех объявленных определений при сборке контейнера, где ключ коллекции это идентификатор контейнера[^ContainerId].

Метод может вернуть только те определения которые были объявлены раньше вызова метода `getDefinitions()`.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::getDefinitions(): iterable
```

Пример использования:

```php
// file: /app/config/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {

    foreach ($configurator->getDefinitions() as $id => $definition) {// [!code highlight]
        // do something.
    }// [!code highlight]

};
```

## `setDefinition()`

Метод `setDefinition()` объявляет определение и его идентификатор контейнера[^ContainerId]. Метод заменит ранее объявленное определение контейнера если совпадают идентификаторы.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::setDefinition(
    string $id,
    mixed $definition
): void
```

Параметры:

- `$id` – идентификатор контейнера.
- `$definition` – определение контейнера.

Пример использования:

```php {10-17}
// file: /app/config/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use App\Services\Mail;

use function Kaspi\DiContainer\{diAutowire, diParameter};
use function getenv;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    if ('STAGES' === getenv('APP_ENV')) {
        $configurator->setDefinition(
            Mail::class,
            diAutowire(Mail::class)
                ->bindArguments(
                    host: diParameter('mail.host.local'),
                    port: diParameter('mail.port.local'),
                );
        );
    }
};
```

## `getDefinition()`

Метод получает по идентификатору контейнера[^ContainerId] объявленное определение контейнера.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::getDefinition(
    string $id,
    ?callable $fallback = null
): mixed
```

Параметры:
- `$id` – идентификатор контейнера определения.
- `$fallback` – резервный обработчик для случая если определение не найдено.

> [!TIP]
> Для параметра `$fallback` применяется типизация `callable(string $id): mixed`.
>
> Если в параметр `$fallback` установлено значение `null` и определение не найдено, то будет выброшено исключение реализующее интерфейс `\Kaspi\DiContainer\Interfaces\Exceptions\NotFoundDefinitionInterface`.


Пример использования:

```php
// file: /app/config/foo_bar_services.php

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\Exception\NotFoundDefinition;

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    // без указания $fallback
    try {
        $configurator->getDefinition('foo_bar');// [!code highlight]
    } catch (NotFoundDefinition) {// [!code warning]
        $configurator->setDefinition(
            'foo_bar',
            diAutowire(FooBar::class)
                ->bindArguments('foo', 'bar')
        );
    }

    // Использовать $fallback
    $qux = $configurator->getDefinition('qux', static fn () => null);// [!code highlight]

    if (null === $qux) {
        $configurator->setDefinition(
            'qux',
            diAutowire(Qux::class)
                ->setup('doConfig')
        );
    }
};
```

## `findTaggedDefinitions()`

Метод `findTaggedDefinitions()` возвращает коллекцию всех объявленных определений отмеченных тегом, где ключ коллекции это идентификатор контейнера[^ContainerId].

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::findTaggedDefinitions(
    string $tag
): iterable
```

Параметры:
- `$tag` – имя тега, FQCN[^FQCN] интерфейса.

> [!TIP]
> В параметре `$tag` можно указывать [FQCN интерфейса](../../tags/interface-as-tag.md).

Пример использования:

```php
// file: /app/config/services_tagged.php

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;
use App\Interfaces\FooInterface;
use App\Services\Client;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $definitions = $configurator->findTaggedDefinitions(FooInterface::class);// [!code highlight]
    
    foreach ($definitions as $id => $definition) {// [!code highlight]
        $definition->setup('setClient', diAutowire(Client::class));
    }// [!code highlight]
};
```

## `removeDefinition()`

Метод `removeDefinition()` исключает идентификатор[^ContainerId] из контейнера и удаляет объявление определения если оно было сделано ранее.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::removeDefinition(
    string $id,
): void
```

Параметры:

- `$id` – идентификатор контейнера.

Все исключенные идентификаторы контейнера будут недоступны для внедрения в параметры.

При получении через метод контейнера `has()` будет возвращаться `false`, а при попытке получить исключенный идентификатор через метод контейнера `get()` будет выброшено исключение реализующее интерфейс `\Psr\Container\NotFoundExceptionInterface`.

Пример использования:

```php
// file: /app/config/services_removed.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use App\Services\Qux;

use function getenv;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    if ('STAGES' === getenv('APP_ENV') {
        $configurator->removeDefinition(Qux::class);// [!code highlight]
    }
};
```

<!--@include: ../../_include/term_notes.md-->
