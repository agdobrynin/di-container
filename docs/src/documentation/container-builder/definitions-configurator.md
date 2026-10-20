---
outline: [2, 4]
---
# Конфигуратор определений { #title } 

## Обзор { #overview }

Через **Конфигуратор определений** в можно объявлять или удалять определения контейнера, использовать другие файлы конфигураций, регистрировать параметры контейнера и использовать контекст для построения логики объявления определений контейнера.

Конфигуратор передается в анонимную функцию первым параметром для [файлов конфигураций](configuration_files.md).

```php
// file: /app/config/services.php
use Generator;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    // …
};
```

## Методы конфигуратора определений { #methods }

Конфигуратор реализует интерфейс `\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface` и предоставляет следующие методы:

- Объявление и получение определений контейнера:
  - `getDefinitions()` – получает все объявленные определения.
  - `setDefinition()` – объявить определение контейнера.
  - `getDefinition()` – получить определение по идентификатору контейнера. 
  - `findTaggedDefinitions()` – получить определения контейнера по тегу.
  - `removeDefinition()` – удаляет объявление из контейнера.
- Объявление определений из файлов конфигураций:
  - `load()` – объявление определений контейнера из файлов конфигураций.
  - `loadOverride()` – объявление определений контейнера из файлов конфигураций с заменой.
- Управление параметрами контейнера:
  - `loadParameters()` – регистрация параметров контейнера из файлов.
  - `addParameters()` – регистрация параметров контейнера из коллекции.
  - `setParameter()` – регистрация параметра контейнера.
  - `removeParameter()` – удаление параметра контейнера.
  - `hasParameter()` – доступность параметра контейнера.
- Контекст для конфигурационных файлов:
  - `getContext()` – получить контекст.

### Объявление и получение определений контейнера { #methods-container-definitions }

При сборке контейнера через `\Kaspi\DiContainer\DiContainerBuilder` все объявленные определения будут доступны в конфигураторе определений, включая импортированные определения через [метод `DiContainerBuilder::import()`](index.md#dicontainerbuilder-import).

#### `getDefinitions()`

Метод `getDefinitions()` возвращает коллекцию всех объявленных определений при сборке контейнера, где ключ коллекции это идентификатор контейнера[^ContainerId].

Метод может вернуть только те определения которые были объявлены раньше вызова метода `getDefinitions()`.

Сигнатура метода:

```php
DefinitionsConfiguratorInterface::getDefinitions(): iterable
```

Пример использования:

```php
// file: /app/config/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {

    foreach ($configurator->getDefinitions() as $id => $definition) {
        // do something.
    }

};
```

#### `setDefinition()`

Метод `setDefinition()` объявляет определение и его идентификатор контейнера[^ContainerId]. Метод заменит ранее объявленное определение контейнера если совпадают идентификаторы.

Сигнатура метода:

```php
DefinitionsConfiguratorInterface::setDefinition(
    string $id,
    mixed $definition
): void
```

Параметры:

- `$id` – идентификатор контейнера.
- `$definition` – определение контейнера.

Пример использования:

```php
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

#### `getDefinition()`

Метод получает по идентификатору контейнера[^ContainerId] объявленное определение контейнера.

Сигнатура метода:

```php
DefinitionsConfiguratorInterface::getDefinition(
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
        $configurator->getDefinition('foo_bar');
    } catch (NotFoundDefinition) {
        $configurator->setDefinition(
            'foo_bar',
            diAutowire(FooBar::class)
                ->bindArguments('foo', 'bar')
        );
    }

    // Использовать $fallback
    if (null === $configurator->getDefinition('qux', static fn () => null)) {
        $configurator->setDefinition(
            'qux',
            diAutowire(Qux::class)
                ->setup('doConfig')
        );
    }
};
```

#### `findTaggedDefinitions()`

Метод `findTaggedDefinitions()` возвращает коллекцию всех объявленных определений отмеченных тегом, где ключ коллекции это идентификатор контейнера[^ContainerId].

Сигнатура метода:

```php
DefinitionsConfiguratorInterface::findTaggedDefinitions(
    string $tag
): iterable
```

Параметры:
- `$tag` – имя тега, FQCN[^FQCN] интерфейса.

> [!TIP]
> В параметре `$tag` можно указывать [FQCN интерфейса](../tags/interface-as-tag.md).

Пример использования:

```php
// file: /app/config/services_tagged.php

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;
use App\Interfaces\FooInterface;
use App\Services\Client;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $definitions = $configurator->findTaggedDefinitions(FooInterface::class);
    
    foreach ($definitions as $id => $definition) {
        $definition->setup('setClient', diAutowire(Client::class));
    }
};
```

#### `removeDefinition()`

Метод `removeDefinition()` исключает идентификатор[^ContainerId] из контейнера и удаляет объявление определения если оно было сделано ранее.

Сигнатура метода:

```php
DefinitionsConfiguratorInterface::removeDefinition(
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
        $configurator->removeDefinition(Qux::class);
    }
};
```

### Объявление определений из файлов конфигураций { #methods-load-from-files }

В [файлах конфигураций](configuration_files.md) доступно объявление определений из других файлов конфигураций.

#### `load()`

Метод `load()` объявляет определения из файлов конфигураций с отслеживанием уникальности идентификаторов контейнера [^ContainerId].

Сигнатура метода:

```php
DefinitionsConfiguratorInterface::load(
    string $file,
    string ...$_
): void
```

Параметры:

- `$file` – полный путь к файлу конфигураций определений.
- `$_` – полный путь к файлу конфигураций определений.

Пример использования:

```php
// file: /app/config/services/mix_services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $configurator->load('/app/config/config_one.php');
};
```

#### `loadOverride()`

Метод `loadOverride()` объявляет определения из файлов конфигураций с возможностью переопределения ранее объявленных определений по тому же идентификатору контейнера [^ContainerId].

Сигнатура метода:

```php
DefinitionsConfiguratorInterface::loadOverride(
    string $file,
    string ...$_
): void
```

Параметры:

- `$file` – полный путь к файлу конфигураций определений.
- `$_` – полный путь к файлу конфигураций определений.

### Управление параметрами контейнера { #methods-container-params }

#### `loadParameters()`

#### `addParameters()`

#### `setParameter()`

#### `removeParameter()`

#### `hasParameter()`

### Контекст для конфигурационных файлов { #context }

#### `getContext()`

🚧🚧🚧🚧🚧




## Использование контекста для конфигурационных файлов.
При сборке и настройке контейнера можно определить дополнительный контекст для конфигурационных файлов.

Контекст определятся самим разработчиком как коллекция ключ-значение.
Передать в коллекцию по ключу можно любой тип – `mixed`, например объект, массив значений и т.д.
```php
DefinitionsConfiguratorInterface::getContext(string $name, ?callable $fallback = null): mixed
```
Параметры:
- `$name` – имя контекста.
- `$fallback`  – обработчик если контекст не найден по имени.

> [!WARNING]
> Метод `getContext()` выбросит исключение `\Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface`
> если контекст не найден по имени.
> 
> Чтобы избежать выброса исключения можно использовать `callable` тип в параметре `$fallback` который принимает в качестве аргумента параметр `$name`:
> ```php
>  callable(string $name): mixed
> ```


Передать значения можно через методы:
- `\Kaspi\DiContainer\Interfaces\DiContainerBuilder::addConfiguratorContexts()`
- `\Kaspi\DiContainer\Interfaces\DiContainerBuilder::setConfiguratorContext()`

Описание методов доступно в разделе «[Передача контекста для конфигурационных файлов](../00-container-builder.md#передача-контекста-для-конфигурационных-файлов)»

### Пример использования контекста для конфигурационных файлов.
```php
use Kaspi\DiContainer\DiContainerBuilder;
use Dotenv\Dotenv;

$builder = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load('/app/config/services/services.php');

// ...
$dotenv = Dotenv::createImmutable('/app/');
$dotenv->required('DATABASE_DSN');
$dotenv->load();

$builder->setConfiguratorContext('DATABASE_DSN', $_ENV['DATABASE_DSN']);

/*
 * объект содержащий нужные данные для конфигурирования контейнера
 * @var \App\Core $core
 */ 
$builder->setConfiguratorContext($core::class, $core);

// ...

$container = $builder->build();
```
```php
// /app/config/services/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {

    $dsn = $configurator->getContext('DATABASE_DSN');
    $core = $configurator->getContext(\App\Core::class);

    yield diAutowire(\App\Services\Foo::class)
        ->bindArguments(
            dsn: $dsn,
            foo: $core->calculateFoo(),
        );
};
```

## Конфигурирование параметров контейнера.
В конфигурационных файлах поддерживается управление «параметрами контейнера».
Подробное [описание доступных методов для параметров контейнера](../container-parameters/index.md#управление-параметрами-контейнера-в-конфигураторе-определений).


<!--@include: ../_include/term_notes.md-->
