Метод передачи аргументов `bindArguments()` для параметров описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface`.

Сигнатура метода:

```php
DiDefinitionArgumentsInterface::bindArguments(
    mixed ...$argument
)
```

Параметры:
- `$argument` – аргументы.

При указании в методе `bindArguments()` как внедрять зависимость в конкретный параметр, можно использовать [именованные аргументы](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments).

Контейнер внедрит зависимости самостоятельно на основании конфигурации, если в аргументах не указано как внедрять зависимость в конкретный параметр.


> [!TIP]
> - Параметр `$argument` в методе `bindArgument()` может принимать хелпер функции такие как `diGet()`, `diValue()`, `diAutowire()` и другие.
> - Для внедрения скалярных типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](../../container-parameters/index.md)».

> [!WARNING]
> Метод `bindArguments()` перезаписывает ранее добавленные аргументы.
