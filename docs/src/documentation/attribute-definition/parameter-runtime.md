---
aside: false
---
# ParameterRuntime

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\ParameterRuntime` внедрят зависимость из параметра контейнера времени исполнения[^ParameterRuntime].

Конфигурация атрибута:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\ParameterRuntime::__construct(
    string $name = '',
    ?string $message = null
)
```

Параметры:
- `$name` – имя параметра контейнера.
- `$message` – дополнительное сообщение, если параметр контейнера ещё не имеет значения.

> [!TIP]
> Изучите отдельный раздел документации [«Параметры контейнера» времени выполнения](../container-parameters/inject_runtime_params.md).


<!--@include: ../_include/term_notes.md-->
