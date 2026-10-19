# Parameter

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Parameter` внедряет зависимость из «параметров контейнера».

Конфигурация атрибута:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Parameter::__construct(
    string $name = ''
)
```
Параметры:
- `$name` – имя параметра контейнера.

> [!TIP]
> Более подробное описание в разделе [«Параметры контейнера»](../container-parameters/inject_params.md).
