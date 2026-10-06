Метод передачи аргументов для параметров описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface`.

Сигнатура метода:

```php
DiDefinitionArgumentsInterface::bindArguments(
    mixed ...$argument
)
```

Параметры:
- `$argument` – аргументы.

> [!TIP]
> - Для параметров не объявленных через метод `bindArgument()` контейнер попытается внедрить зависимости самостоятельно,
> включая [использование php атрибутов](../../02-attribute-definition.md).
> - Параметр `$argument` в методе `bindArgument()` может принимать хелпер функции такие как `diGet()`, `diValue()`, `diAutowire()` и другие.
> - Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](../../09-container-parameters.md)».

> [!WARNING]
> Метод `bindArguments()` перезаписывает ранее добавленные аргументы.
