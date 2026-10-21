# Динамическое добавление определений в контейнер

## Обзор { #overview }

Прямая установка объекта (_инстанцированный класс_) в уже сформированный контейнер зависимостей:

```php
\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface::set(
    string $id,
    mixed $definition
): static;
```

Параметры:
- `$id` – идентификатор контейнера [^ContainerId] или FQCN [^FQCN].
- `$definition` – объект.

> [!WARNING]
> Если идентификатор контейнера не уникален в рамках текущего контейнера
>, то будет выброшено исключение `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierAlreadyRegisteredExceptionInterface`

> [!WARNING]
> Рекомендуется использовать [файлы конфигурации](configuration_files.md),
> [добавлять определения из коллекции](index.md#load-definitions-from-collection)
> или [импортировать классы и интерфейсы](index.md##dicontainerbuilder-import)
> через класс-строитель `DiContainerBuilder`,
> так как определения установленные напрямую в контейнер не будут [скомпилированы](index.md#compile-to-file).
>
> В некоторых сценариях при использовании метода `set()` необходимо отслеживать чтобы получение сервиса
> через метод контейнера `get()` не вызывало ошибки из-за того что определение ещё необавлено в контейнер.
>

## Пример использования метода контейнера `set()` { #example }

```php
use App\Services\Others;

$others = new Others(
    // set some dependencies.
);

// идентификатор будет указан как 'App\\Services\\Others'
$container->set($others::class, $others);
```

Файлы участвующие в конфигурации:

::: code-group

```php [container_builder.php]
// file: /app/src/container_builder.php
require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;

// конфигурация и получение готового контейнера зависимостей
$container = (new DiContainerBuilder())
    ->import(
        namespace: 'App\\',
        src: '/app/src/',
        excludeFiles: [
            // исключить из автоматической настройки
            '*/src/Services/Others.php',
        ]
    )
    
    // другие настройки контейнера
    
    ->build()
;
```
```php [Others.php]
// file: /app/src/Services/Others.php
namespace App\Services;

use App\Services\Others;

// ⚠️ Класс который нужно конфигурировать отдельно.
final class Others {
    public function __construct(
        // some dependencies
    ) {}
}
```
```php [Foo.php]
// app/src/Services/Foo.php
namespace App\Services;

use App\Services\Others;

final class Foo {
    public function __construct(public readonly Others $others) {}
}
```
:::

> [!WARNING]
> Установка в контейнер нового определения должно быть до вызова метода контейнера `get()`
> который может внедрить зависимость с типом `'\App\Services\Others'`.

> [!IMPORTANT]
> Для корректной компиляции контейнера с определениями использующими
> «динамические определения» в своих зависимостях, которое может быть установлено только
> в уже сформированный контейнер (_runtime_),
> следует использовать в конфигурационных файлах [хелпер функцию `diRuntime()`](../runtime-definition/index.md#diruntime) использование
> которой описано в разделе «[Внедрение экземпляра класса в рантайм контейнер](../runtime-definition/index.md)».

<!--@include: ../_include/term_notes.md-->