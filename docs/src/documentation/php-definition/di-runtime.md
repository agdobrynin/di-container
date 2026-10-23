# diRuntime()

## Обзор { #overview }

Хелпер функция `diRuntime()` объявляет PHP класс в контейнере, реальное состояние которого будет инициализировано вне контейнера, и его значение будет установлено только во время выполнения.

Такой PHP класс в рамках контейнера зависимостей называется **«[Runtime Definition](../runtime-definition/index.md)»**.

> [!WARNING]
> Хелпер функция не может быть применена к параметрам метода или функции. Для внедрения в параметры методов или вызываемых типов используйте [хелпер функцию `diGet()`](di-get.md)

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

- `$containerIdentifierOrClass` – идентификатора контейнера[^ContainerId] для «runtime definition».
- `$message` – сообщение при возникновении ошибки.
- `$classDefinition` – резервное объявление FQCN[^FQCN] объекта.

Функция `diRuntime()` возвращает объект предоставляющий методы:

- `bindTag()` – тег с метаданными.
- `setResetter()` - конфигурация для сброса состояния объекта.

> [!TIP]
> Изучите раздел документации «[Конфигурирование объекта в рантайм контейнер](../runtime-definition/index.md)».

## bindTag()

Теги позволяют отнести «runtime definition» к коллекции элементов отмеченных одним тегом.

<!--@include: ./_include/bind_tag.md-->

## setResetter()

<!--@include: _include/set-resetter.md-->

## Идентификатор контейнера { #container-id }

Хелпер функция `diRuntime()` может передавать контейнеру идентификатор [^ContainerId] из параметра `$containerIdentifierOrClass`, что обеспечивает доступ к нему через методы контейнера `get()` и `has()`.

```php
<?php
declare(strict_types=1);

// file: /app/config/runtime_definitions.php // [!code highlight]

use App\Core\Kernel;

use function Kaspi\DiContainer\diRuntime;

return static function (): Generator {
    yield diRuntime(Kernel::class);
    /*
     * 🚩 Эквивалентно объявлению // [!code highlight]
     * Kernel::class => diRuntime(Kernel::class); // [!code warning]
     */
};
```

> [!NOTE]
> Пример файла конфигураций выше позволяет получить объект методом контейнера `$container->get(\App\Core\Kernel::class)`.


Так же доступно объявление объекта с произвольным строковым идентификатором контейнера[^ContainerId]:

```php
<?php
declare(strict_types=1);

// file: /app/config/runtime_definitions.php // [!code highlight]

use App\Core\Kernel;

use function Kaspi\DiContainer\diRuntime;

return static function (): Generator {
    yield 'services.core_kernel' => diRuntime(Kernel::class);
};
```

> [!NOTE]
> Пример файла конфигураций выше позволяет получить объект методом контейнера `$container->get('services.core_kernel')`.

<!--@include: ../_include/term_notes.md-->