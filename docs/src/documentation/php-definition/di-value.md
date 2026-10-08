# diValue

## Обзор

Хелпер функция `diValue()` внедряет зависимость «как есть».

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
 
\Kaspi\DiContainer\diValue(
    mixed $value
): DiDefinitionTagArgumentInterface
```

Параметры:
- `$value` – переданное значение в контейнер.

Функция `diValue()` возвращает объект предоставляющий методы:
- `bindTag()` – добавляет тег с мета-данными для определения.

## bindTag()

Теги позволяют отнести конфигурируемый вызываемый тип к коллекции сервисов.

<!--@include: ./_include/bind_tag.md-->

## Идентификатор контейнера { #container-id }

Хелпер функция `diValue()` не может автоматически сформировать идентификатор контейнера,
поэтому необходимо указать идентификатор контейнера для определения конфигурируемого через эту хелпер функцию.

```php
// config/emails.php
use function Kaspi\DiContainer\diValue;

return static function () {

    yield 'admin.email.tasks' => diValue('runner@company.inc');

};
```

<!--@include: ../_include/term_notes.md-->

