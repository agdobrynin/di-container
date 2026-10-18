# diRuntime

## Обзор { #overview }

Хелпер функция `diRuntime()` внедряет «[runtime definition](../runtime-definition/index.md)».

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\{
    DiDefinitionTagArgumentInterface as Tag,
    DiDefinitionResetterSetterInterface as Resetter
};

\Kaspi\DiContainer\diRuntime(
    string $containerIdentifierOrClass,
    ?string $message = null,
    ?string $classDefinition = null
): Tag & Resetter
```

Параметры:

- `$containerIdentifierOrClass` – идентификатора контейнера[^ContainerId] или FQCN[^FQCN] «runtime definition».
- `$message` – информационное сообщение при возникновении ошибки.
- `$classDefinition` – указание FQCN[^FQCN] для «runtime definition» если параметр `$containerIdentifierOrClass` простое строковое значение.

----
🚧🚧🚧🚧

> [!IMPORTANT]
> Функция `diRuntime` возвращает объект реализующий интерфейсы:
> - `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`,
> - `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterSetterInterface`.

>
> Методы доступные при вызове хелпер функции:
>   - `bindTag` - добавляет тег с мета-данными для определения.
>   - `setResetter` - [конфигурация сброса состояния объекта](../12-object-resetters.md).
>
> [Пример использования тегов](#пример-тегирования-runtime-definition).

> [!WARNING]
> Хелпер функция не может быть применена к параметрам метода класса или `callable` типа.
>

> [!NOTE]
> `$containerIdentifierOrClass` может быть представлен любой не пустой строкой
> чтобы сервис можно было получить через метод контейнера `get()`.
>
> Хелпер функция `diRuntime()` не создает конфигурацию экземпляра класса, а только предоставляет идентификатор контейнера.
>

> [!NOTE]
> [Пример конфигурирования через хелпер функцию `diRuntime`](#пример-использования-хелпер-функции-diruntime-в-конфигурационных-файлах).

<!--@include: ../_include/term_notes.md-->