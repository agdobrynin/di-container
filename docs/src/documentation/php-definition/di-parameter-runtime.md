# diParameterRuntime

## Обзор { #overview }

Хелпер функция для конфигурирования параметра контейнера времени исполнения[^1].

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

[^1]: Параметр «времени исполнения» устанавливает своё реальное значение в уже сформированный контейнер зависимостей (_runtime container_),
и предназначен для корректной конфигурации определений контейнера которые зависят от такого параметра.