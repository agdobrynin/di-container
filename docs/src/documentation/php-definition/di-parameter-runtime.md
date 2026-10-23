---
aside: false
---
# diParameterRuntime()

## Обзор { #overview }

Хелпер функция `diParameterRuntime()` внедрят зависимость из параметров контейнера времени исполнения[^ParameterRuntime].

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
> Изучите отдельный раздел документации посвященный [параметрами контейнера](../container-parameters/inject_runtime_params.md).

<!--@include: ../_include/term_notes.md-->
