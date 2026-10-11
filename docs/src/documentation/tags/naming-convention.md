# Соглашение по именованию тегов и мета-данных { #title }

Рекомендовано придерживаться соглашения по именованию значений для имён тегов, и мета-данных для тега:
- использовать строчные буквы для имён тегов и ключей в мета-данных.
- разделять символом подчеркивание `_` части имени если определение состоит из нескольких слов.
- разделять символом точка `.` группы в определении.

### Пример использования имён по соглашению { #example-naming-convention }

Конфигурирование в стиле PHP определений:

```php
use function Kaspi\DiContainer\diAutowire;

diAutowire(SomeClass::class)
    ->bindTag(
        'tags.any_service',
         options: [
            'some_info.data' => 'foo',
            'some_info.current_value' => 'baz',
        ]
     );
```

Конфигурирование через PHP атрибуты:

```php
use Kaspi\DiContainer\Attributes\Tag;

#[Tag(
    'tags.any_service',
    options: [
        'some_info.data' => 'foo',
        'some_info.current_value' => 'baz',
    ]
)]
final class Foo {}
```
