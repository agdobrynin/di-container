---
aside: false
---
# Конфигурация для DiContainer { #title }

Для конфигурации контейнера используется класс `\Kaspi\DiContainer\DiContainerConfig`.

Настройки по умолчанию предоставляют оптимальную конфигурацию для работы контейнера в большинстве случаев.

Сигнатура конструктора конфигурации:

```php
\Kaspi\DiContainer\DiContainerConfig::__construct(
    useZeroConfigurationDefinition: true,
    useAttribute: true,
    isSingletonServiceDefault: false,
    isConfigureObjectResettersFromDefinitions: true,
)
```

Параметры:

* `$useZeroConfigurationDefinition`<span id="use-zero-configuration-definition"/> – нулевая конфигурация для внедрения зависимостей.
  > Не нужно указывать контейнеру, как внедрять конкретный PHP-класс если класс не имеет зависимостей,
или зависит только от других конкретных классов, или зависит от ранее сконфигурированных классов (интерфейсов).

* `$isUseAttribute`<span id="use-attribute"/> – использовать атрибуты для конфигурации определений контейнера.
  > Параметр определяет возможность [конфигурирования определений на базе PHP атрибутов](../attribute-definition/index.md).

* `$isSingletonServiceDefault`<span id="is-singleton-service-default"/> – получать новый инстанс объекта каждый раз, значение по-умолчанию `false`.
  > Для определений в контейнере можно указать как создавать объект: возвращать всегда одни и тот же объект
или создавать объект каждый раз при получении через метод контейнера `get()`.
Для определений контейнера у которых неуказан способ получения через метод контейнера `get()`
применяется значение по умолчанию из конфигурации.

* `$isConfigureObjectResettersFromDefinitions` – конфигурировать автоматически сервис «Сброс состояния объектов» из определений контейнера.
  > В долгоживущих процессах некоторые сервисы контейнера могут требовать сброса своего состояния.
Для сброса состояния таких сервисов может быть использован механизм автоматического конфигурирования
сервиса на основе конфигурации определений контейнера. Подробности в разделе [«Сброс состояния объектов для долго-живущих процессов».](../12-object-resetters.md)

Измененная конфигурация контейнера передается через параметр `$containerConfig` конструктора класса `\Kaspi\DiContainer\DiContainerBuilder`.

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
