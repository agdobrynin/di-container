# Управление параметрами контейнера { #title }

## Обзор { #overview } 

В файлах конфигураций доступна регистрация и управление [параметрами контейнера](../../container-parameters/index.md).

```php
<?php
declare(strict_types=1);

// file: /app/config/services/services.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    $configurator->loadParameters('/app/config/parameters/app_params.php'); // [!code ++]
    $configurator->setParameter('params.baz', 'Lorem ipsum');// [!code ++]

    yield diAutowire(Foo::class)
        ->bindArguments(
            diParameter('params.foo')
        );
};
```

## loadParameters()

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

## `addParameters()`

Метод `addParameters()` регистрирует параметры контейнера из коллекции.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::addParameters(
    iterable $params
): void
```

Параметры:
- `$params` – коллекция ключ-значение конфигурации параметров.

## setParameter()

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

## removeParameter()

Метод `removeParameter()` удаляет параметр контейнера по имени из конфигурации.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::removeParameter(
    string $name
): void
```

Параметры:

- `$name` – имя параметра.

## hasParameter()

Метод `hasParameter()` выполняет проверку наличия определенного параметра контейнера в конфигурации по имени параметра.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::removeParameter(
    string $name
): void
```

Параметры:

- `$name` – имя параметра.

<!--@include: ../../_include/term_notes.md-->
