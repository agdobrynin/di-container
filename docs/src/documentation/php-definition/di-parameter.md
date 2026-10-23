---
aside: false
---
# diParameter()

## Обзор { #overview }

Хелпер функция `diParameter()` внедрят зависимость из параметров контейнера[^Parameter].

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
> Изучите отдельный раздел документации посвященный [параметрами контейнера](../container-parameters/inject_params.md).

<!--@include: ../_include/term_notes.md-->
