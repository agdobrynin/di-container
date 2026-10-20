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

return static function (
    DefinitionsConfiguratorInterface $configurator //[!code ++]
): Generator {
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

#### `setDefinition()`

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

#### `getDefinition()`

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

#### `findTaggedDefinitions()`

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
> В параметре `$tag` можно указывать [FQCN интерфейса](../tags/interface-as-tag.md).

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

#### `removeDefinition()`

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

### Объявление определений из файлов конфигураций { #methods-load-from-files }

В [файлах конфигураций](configuration_files.md) доступно объявление определений из других файлов конфигураций.


```php
// file: /app/config/services/mix_services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $configurator->load('/app/config/config_one.php');// [!code highlight]
    
    if ('STAGES' === getenv('APP_ENV') {
        $configurator->loadOverride('/app/config/services_for_stages.php');// [!code highlight]
    }
};
```

#### `load()`

Метод `load()` объявляет определения из файлов конфигураций с отслеживанием уникальности идентификаторов контейнера [^ContainerId].

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::load(
    string $file,
    string ...$_
): void
```

Параметры:

- `$file` – полный путь к файлу конфигураций определений.
- `$_` – полный путь к файлу конфигураций определений.

#### `loadOverride()`

Метод `loadOverride()` объявляет определения из файлов конфигураций с возможностью переопределения ранее объявленных определений по тому же идентификатору контейнера [^ContainerId].

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::loadOverride(
    string $file,
    string ...$_
): void
```

Параметры:

- `$file` – полный путь к файлу конфигураций определений.
- `$_` – полный путь к файлу конфигураций определений.

### Управление параметрами контейнера { #methods-container-params }

В файлах конфигураций доступна регистрация и управление [параметрами контейнера](../container-parameters/index.md).

```php
// file: /app/config/services/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    $configurator->loadParameters('/app/config/parameters/app_params.php'); // [!code highlight]
    $configurator->setParameter('params.baz', 'Lorem ipsum');// [!code highlight]

    yield diAutowire(Foo::class)
        ->bindArguments(
            diParameter('params.foo')
        );
};
```

#### `loadParameters()`

Метод `loadParameters()` регистрирует параметры контейнера через файлы конфигураций параметров.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::loadParameters(
    string $file,
    string ...$_
): void
```

Параметры:

- `$file` – полный путь к файлу описывающий конфигурацию параметров.
- `$_` – дополнительные файлы конфигураций параметров контейнера.

#### `addParameters()`

Метод `addParameters()` регистрирует параметры контейнера из коллекции.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::addParameters(
    iterable $params
): void
```

Параметры:
- `$params` – коллекция ключ-значение конфигурации параметров.

#### `setParameter()`

Метод `setParameter()` регистрирует имя параметра контейнера и его значение.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::setParameter(
    string $name,
    array|int|float|string|bool|null|\UnitEnum $value
): void
```

Параметры:
- `$name` – имя параметра.
- `$value` – значение параметра.

#### `removeParameter()`

Метод `removeParameter()` удаляет параметр контейнера по имени из конфигурации.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::removeParameter(
    string $name
): void
```

Параметры:

- `$name` – имя параметра.

#### `hasParameter()`

Метод `hasParameter()` выполняет проверку наличия определенного параметра контейнера в конфигурации по имени параметра.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::removeParameter(
    string $name
): void
```

Параметры:

- `$name` – имя параметра.

### Контекст для файлов конфигураций { #context }

При сборке и настройке контейнера можно определить дополнительный контекст для файлов конфигураций.

Контекст определятся самим разработчиком как коллекция ключ-значение. Передать в коллекцию по ключу можно любой тип – `mixed`, например объект, массив значений или другой желаемый тип.

Методы [`DiContainerBuilder::addConfiguratorContexts()`](index.md##dicontainerbuilder-addconfiguratorcontexts) и [`DiContainerBuilder::setConfiguratorContext()`](index.md#dicontainerbuilder-setconfiguratorcontext) устанавливают контекст для файлов конфигураций.


#### `getContext()`

Метод `getContext()` получает значение контекста по имени.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::getContext(
    string $name,
    ?callable $fallback = null
): mixed
```

Параметры:

Параметры:
- `$name` – имя контекста.
- `$fallback`  – резервный обработчик для случая если контекст не найден по имени.


> [!TIP]
> Для параметра `$fallback` применяется типизация `callable(string $name): mixed`.
>
> Если в параметр `$fallback` установлено значение `null` и контекст не найден, то будет выброшено исключение реализующее интерфейс `\Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface`.

##### Пример использования контекста в файлах конфигураций { #example-context }

В файле конфигураций `/app/app/config/foo_service.php` требуется контекст для объявления PHP класса – нужно значение для параметра `\App\Services\Foo::$dsn` для настройки базы данных, и требуется вычисляемое значение получаемое в методе PHP класса `\App\Core::calculateFoo()` для параметра для параметра `\App\Services\Foo::$foo`.

Настройки подключения к базе хранятся в переменной окружения `DATABASE_DSN`.

Сборка контейнера:

::: code-group

```php [build_container.php]
use Kaspi\DiContainer\DiContainerBuilder;
use Dotenv\Dotenv;

$builder = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load(
        '/app/config/services/services.php',
        '/app/app/config/foo_service.php',// [!code ++]
    );

// Использую стороннюю библиотеку для работы с `.env` файлами.
$dotenv = Dotenv::createImmutable('/app/');// [!code highlight]
$dotenv->required('DATABASE_DSN');// [!code highlight]
$dotenv->load();// [!code highlight]

$builder->setConfiguratorContext('DATABASE_DSN', $_ENV['DATABASE_DSN']);// [!code ++]

/*
 * объект содержащий нужные данные для конфигурирования контейнера
 * @var \App\Core $core
 */ 
$builder->setConfiguratorContext($core::class, $core);// [!code ++]

// ...

$container = $builder->build();
```

```php [foo_service.php]
// file: /app/app/config/foo_service.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Generator;
use App\Core;
use App\Services\Foo;

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {

    $dsn = $configurator->getContext('DATABASE_DSN');
    $core = $configurator->getContext(Core::class);

    yield diAutowire(Foo::class)
        ->bindArguments(
            dsn: $dsn,
            foo: $core->calculateFoo(),
        );
};
```

:::

<!--@include: ../_include/term_notes.md-->
