# Контекст для файлов конфигураций { #title }

## Обзор { #overview }

При сборке и настройке контейнера можно определить дополнительный контекст для файлов конфигураций.

Контекст определятся самим разработчиком как коллекция ключ-значение. Передать в коллекцию по ключу можно любой тип – `mixed`, например объект, массив значений или другой желаемый тип.

Методы [`DiContainerBuilder::addConfiguratorContexts()`](../methods/configuration-context.md#addconfiguratorcontexts) и [`DiContainerBuilder::setConfiguratorContext()`](../methods/configuration-context.md#setconfiguratorcontext) устанавливают контекст для файлов конфигураций.


## getContext()

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

## Пример использования контекста в файлах конфигураций { #example-context }

В файле конфигураций `/app/app/config/foo_service.php` требуется контекст для объявления PHP класса – нужно значение для параметра `\App\Services\Foo::$dsn` для настройки базы данных, и требуется вычисляемое значение получаемое в методе PHP класса `\App\Core::calculateFoo()` для параметра для параметра `\App\Services\Foo::$foo`.

Настройки подключения к базе хранятся в переменной окружения `DATABASE_DSN`.

Сборка контейнера:

::: code-group

```php [build_container.php]
// file: /app/src/build_container.php
require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

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

<!--@include: ../../_include/term_notes.md-->
