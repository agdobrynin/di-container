# diParameterRuntime

## Обзор { #overview }

Хелпер функция `diParameterRuntime()` внедрят зависимость из параметра контейнера времени исполнения[^ParameterRuntime].

Сигнатура функции:

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diParameterRuntime(
    string $name = '',
    ?string $message = null
): DiDefinitionNoArgumentsInterface
```

Параметры:
- `$name` – имя параметра контейнера.
- `$message` – дополнительное сообщение, если параметр контейнера еще не определен.

> [!NOTE]
> Изучите отдельный раздел документации посвященный [параметрами контейнера](../09-container-parameters.md).

<!--@include: ../_include/term_notes.md-->
