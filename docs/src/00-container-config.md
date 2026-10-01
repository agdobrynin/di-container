# Конфигурирование DiContainer

Для конфигурирования контейнера используется класс
`\Kaspi\DiContainer\DiContainerConfig`
который реализует интерфейс
`\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface`.

## Нулевая конфигурация для внедрения зависимостей:
```php
\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isUseZeroConfigurationDefinition(): bool;
```
**Не нужно указывать контейнеру, как разрешить конкретный PHP-класс**
если класс не имеет зависимостей, или зависит только от других конкретных классов,
или зависит от ранее сконфигурированных классов (интерфейсов).

## Использовать Php-атрибуты для конфигурирования:
```php
\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isUseAttribute(): bool;
```
Предоставляет возможность [конфигурирования определений на базе PHP атрибутов](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md).

## Разрешать зависимость как синглтон:
```php
\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isSingletonServiceDefault(): bool;
```
Для определений в контейнере можно указать как разрешать сервис – возвращать всегда одни и тот же объект
или создавать объект сервиса каждый раз при получении через метод контейнера `get()`.
Для определений контейнера у которых неуказан способ получения через метод контейнера `get()`
применяется значение по умолчанию из конфигурации.

## Конфигурировать сервис сброса состояния объектов из определений контейнера:
```php
\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isConfigureObjectResettersFromDefinitions(): bool;
```
В долгоживущих процессах некоторые сервисы контейнера могут требовать сброса своего состояния.
Для сброса состояния таких сервисов может быть использован механизм автоматического конфигурирования
сервиса на основе конфигурации определений контейнера. Подробности в разделе [«Сброс состояния объектов для долго-живущих процессов».](https://github.com/agdobrynin/di-container/blob/main/docs/12-object-resetters.md)

## Пример конфигурации

```php
use Kaspi\DiContainer\{DiContainerConfig, DiContainerBuilder};

$diConfig = new DiContainerConfig(
    useZeroConfigurationDefinition: false,
    useAttribute: false,
    isSingletonServiceDefault: true,
    isConfigureObjectResettersFromDefinitions: false,
);

// передать настройки в построитель контейнера
$container = (new DiContainerBuilder(containerConfig: $diConfig))
    ->build();
```
