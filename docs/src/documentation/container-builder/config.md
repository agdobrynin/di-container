# Установка индивидуальной конфигурации контейнера

Для настройки поведения контейнера можно использовать индивидуальную [настройку конфигурации](../container-config/index.md).

Конфигурация по умолчанию:
```php
use Kaspi\DiContainer\DiContainerConfig;

$diConfig = new DiContainerConfig(
    useZeroConfigurationDefinition: true,
    useAttribute: true,
    isSingletonServiceDefault: false,
    isConfigureObjectResettersFromDefinitions: true,
);
```

При необходимости можно изменить настройки по умолчанию в `DiContainerConfig` и передать конфигурацию
в `DiContainerBuilder`:

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
    ->build()
;
```
