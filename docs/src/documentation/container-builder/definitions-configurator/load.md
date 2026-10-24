# Объявление определений из файлов конфигураций { #title }

## Обзор { #overview }

В [файлах конфигураций](../configuration_files.md) доступно объявление определений из других файлов конфигураций.

```php
<?php
declare(strict_types=1);

// file: /app/config/services/mix_services.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $configurator->load('/app/config/config_one.php');// [!code ++]
    
    if ('STAGES' === getenv('APP_ENV') {
        $configurator->loadOverride('/app/config/services_for_stages.php');// [!code ++]
    }
};
```

## load()

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

## loadOverride()

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

<!--@include: ../../_include/term_notes.md-->
