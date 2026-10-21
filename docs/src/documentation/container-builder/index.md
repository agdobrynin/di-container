---
outline: [2, 4]
---
# Сборка контейнера зависимостей { #title }

## Обзор { #overview }

Комбинация методов класса `\Kaspi\DiContainer\DiContainerBuilder` предоставляет гибкую настройку и сборку контейнера зависимостей.

Цепочка вызовов настройки сборки должна заканчиваться методом `\Kaspi\DiContainer\DiContainerBuilder::build()` для получения настроенного контейнера зависимостей.

Собранный контейнер будет предоставлять стандартные методы `get()`, `has()` из спецификации [PSR-11](https://www.php-fig.org/psr/psr-11/),
[метод `call()`](../call/index.md) и дополнительный [метод `set()`](set.md) для динамического добавления определений в контейнер.

```php
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface as Call;
use Kaspi\DiContainer\Interfaces\DiContainerInterface as PsrContainer;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface as Setter;

/**
 * @var Call&PsrContainer&Setter $container 
 */
$container = (new DiContainerBuilder())
    ->build()
;
```

> [!IMPORTANT]
> Валидация конфигурации контейнера провидится в методе `DiContainerBuilder::build()`.
> При некорректной конфигурации будет выброшено исключение `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface`.

> [!IMPORTANT]
> Методы загрузки [файлов конфигураций](configuration_files.md) имеют следующий приоритет, чем раньше использован метод загрузки, тем приоритетнее объявления сделанные в этом файле. Идентификаторы контейнера[^ContainerId] для определений должны быть уникальными в рамках создаваемого контейнера.
>
> Для некоторых сценариев использования может понадобиться переопределение ранее объявленных определений.
> Для переопределения используйте методы `DiContainerBuilder::addDefinitionsOverride()`
> и `DiContainerBuilder::loadOverride()`.
>
> При возникновении конфликта объявления определений контейнера будет выброшено исключение.


## Методы сборки контейнера { #methods }
 
Класс `\Kaspi\DiContainer\DiContainerBuilder` реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerBuilderInterface` и предоставляет методы для объявления определений контейнера, регистрации параметров контейнера, использования файлов конфигураций, формирования с контекстов для файлов конфигураций и компиляции контейнера в готовый к использованию PHP-код сохраняемый в файл.

[Объявление определений контейнера из файлов конфигураций](methods/load-definitions-from-file.md):
- `load()` – объявляет определения в контейнере из файлов конфигураций.
- `loadOverride()` – объявляет определения в контейнере из файлов конфигураций с перезаписью.

[Объявление определений контейнера через коллекцию](methods/load-definitions-from-collection.md):
- `addDefinitions()` – объявляет определения в контейнере из коллекции.
- `addDefinitionsOverride()` – объявляет определения в контейнере из коллекции с перезаписью.

[Регистрация параметров контейнера](methods/load-parameters.md):
- `loadParameters()` – регистрация параметров контейнера из файлов.
- `addParameters()` – регистрация параметров контейнера из коллекции.
- `setParameter()` – регистрацию одного параметра контейнера и его значения.

[Объявление PHP классов в контейнере из файловой системы](methods/import.md):
- `import()` – объявляет PHP классы через сканирование файловой системы.

[Компиляция контейнера](methods/compile-to-file.md):
- `compileToFile()` – объявляет параметры компиляции контейнера в PHP-код.

🚧🚧🚧🚧🚧🚧🚧🚧

## Передача контекста для конфигурационных файлов { #configuration-context }

В [конфигурационных файлах](#load-definitions-from-file) можно использовать контекст для настройки и сборки контейнера.

> [!TIP]
> Подробное описание и примеры использования описаны в разделе «[Контекст для файлов конфигураций](definitions-configurator/context.md)».

> [!WARNING]
> Значение для ранее добавленных контекстов могут быть заменены если имена контекста совпадают.

### DiContainerBuilder::addConfiguratorContexts()

Передача коллекции контекста для файлов конфигураций:

```php
\Kaspi\DiContainer\Interfaces\DiContainerBuilder::addConfiguratorContexts(
    iterable $contexts
): static
```
Параметры:
- `$contexts` – коллекция ключ-значение, где ключ коллекции это имя контекста;

### DiContainerBuilder::setConfiguratorContext()

Передача одиночного контекста для файлов конфигураций:

```php
\Kaspi\DiContainer\Interfaces\DiContainerBuilder::setConfiguratorContext(
    string $name,
    mixed $context
): static
```
Параметры:
- `$name` – имя контекста, непустая строка;
- `$context` – значение контекста;

## Использование контейнера в разных окружениях приложения { #build-container-env }

Окружения для приложений называются в зависимости от их назначения:
Локальное (**dev**) — для разработчика, Тестовое (**test**) — для QA,
Продакшен (**prod**) — для конечных пользователей.

Не используйте [компиляцию контейнера](#compile-to-file) в среде разработки (_dev_), иначе все изменения, которые вы внесете в определения (атрибуты, файлы конфигурации и т.д.), не будут приняты во внимание. Компиляция конечного файла контейнера происходит только один раз и возвращается всегда экземпляр контейнера сформированного при первой компиляции.

### Как использовать компиляцию в разных средах приложения { #how-to-build-container-env }

Пример изолирования настройки компиляции контейнера в разных средах разработки:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$builder = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    // 🚩 Отслеживать уникальность определений
    ->load(
        '/app/config/base_services.php',
        '/app/config/prod_services.php'
    )
;

// 🚩 окружение для локальной разработки приложения
if ('dev' === \getenv('APP_ENV')) {

    $builder->loadOverride('/app/config/dev_services.php');

}

// 🚩 Компилировать контейнер в файл
// если приложение работает в продуктивной среде 
if ('prod' === \getenv('APP_ENV')) {

    $builder->compileToFile('/app/var/container', 'App\\Core\\FooContainer');

}

$container = $builder->build();
```

> [!TIP]
> При развёртывании в продуктивной среде новой или измененной версии контейнера
> необходим удалить ранее созданный файл контейнера, например запуском shell скрипта для примера выше:
> ```shell
>  rm /app/var/container/FooContainer.php
> ```
> После удаления ранее сгенерированного файла `/app/var/container/FooContainer.php`
> рекомендуется "прогреть" приложение чтобы при первом вызове произошла компиляция
> контейнера. Последующие вызовы контейнера в коде будут на скомпилированном контейнере.

<!--@include: ../_include/term_notes.md-->
