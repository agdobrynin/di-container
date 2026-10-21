# Контекст для конфигурационных файлов { #title }

## Обзор { #overview }

В [конфигурационных файлах](../configuration_files.md) можно использовать контекст для настройки и сборки контейнера.

> [!TIP]
> Подробное описание и примеры использования описаны в разделе «[Контекст для файлов конфигураций](../definitions-configurator/context.md)».

> [!WARNING]
> Значение для ранее добавленных контекстов могут быть заменены если имена контекста совпадают.

## addConfiguratorContexts()

Метод `addConfiguratorContexts()` передаёт коллекцию контекста для файлов конфигураций.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DiContainerBuilder::addConfiguratorContexts(
    iterable $contexts
): static
```
Параметры:
- `$contexts` – коллекция ключ-значение, где ключ коллекции это имя контекста.

## setConfiguratorContext()

Метод `setConfiguratorContext()` передаёт именованный контекст для файлов конфигураций.

Сигнатура метода:

```php
\Kaspi\DiContainer\Interfaces\DiContainerBuilder::setConfiguratorContext(
    string $name,
    mixed $context
): static
```
Параметры:
- `$name` – имя контекста, непустая строка.
- `$context` – значение контекста.

<!--@include: ../../_include/term_notes.md-->
