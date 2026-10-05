# diParameter

## Обзор { #overview }

Хелпер функция для конфигурирования параметра контейнера[^1].

Сигнатура функции:

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diParameter(
    string $name = ''
): DiDefinitionNoArgumentsInterface
```

Параметры:
- `$name` – имя параметра контейнера.

> [!NOTE]
> Изучите отдельный раздел документации посвященный [параметрами контейнера](../09-container-parameters.md).

[^1]: Параметр контейнера это простой скалярный тип который можно повторно использовать для указания как разрешить зависимость.
