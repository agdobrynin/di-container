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
> Валидация конфигурации контейнера провидится в методе `DiContainerBuilder::build()`. При некорректной конфигурации будет выброшено исключение реализующее интерфейс `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface`.

> [!IMPORTANT]
> [Методы загрузки из файлов конфигураций](methods/load-definitions-from-file.md) имеют следующий приоритет, чем раньше использован метод загрузки, тем приоритетнее объявления сделанные в этом файле. Идентификаторы контейнера[^ContainerId] для определений должны быть уникальными в рамках создаваемого контейнера.
>
> Для некоторых сценариев использования может понадобиться переопределение ранее объявленных определений.
> Для переопределения используйте методы [`DiContainerBuilder::addDefinitionsOverride()`](methods/load-definitions-from-collection.md#adddefinitionsoverride)
> и [`DiContainerBuilder::loadOverride()`](methods/load-definitions-from-file.md#loadoverride).
>
> При возникновении конфликта объявления определений контейнера будет выброшено исключение.


## Методы сборки контейнера { #methods }
 
Класс `\Kaspi\DiContainer\DiContainerBuilder` реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerBuilderInterface` и предоставляет методы для объявления определений контейнера, регистрации параметров контейнера, формирования с контекстов для файлов конфигураций и компиляции контейнера в готовый к использованию PHP-код сохраняемый в файл.

[Объявление определений контейнера из файлов конфигураций](methods/load-definitions-from-file.md):
- `load()` – объявляет определения в контейнере из файлов конфигураций.
- `loadOverride()` – объявляет определения в контейнере из файлов конфигураций с перезаписью.

[Объявление определений контейнера через коллекцию](methods/load-definitions-from-collection.md):
- `addDefinitions()` – объявляет определения в контейнере из коллекции.
- `addDefinitionsOverride()` – объявляет определения в контейнере из коллекции с перезаписью.

[Регистрация параметров контейнера](methods/load-parameters.md):
- `loadParameters()` – регистрация параметров контейнера из файлов.
- `addParameters()` – регистрация параметров контейнера из коллекции.
- `setParameter()` – регистрация параметра контейнера и его значения.

[Объявление PHP классов в контейнере из файловой системы](methods/import.md):
- `import()` – объявляет PHP классы через сканирование файловой системы.

[Компиляция контейнера](methods/compile-to-file.md):
- `compileToFile()` – объявляет параметры компиляции контейнера в PHP-код.

[Передача контекста для конфигурационных файлов](methods/configuration-context.md):
- `addConfiguratorContexts()` – коллекция контекста для файлов конфигураций.
- `setConfiguratorContext()` – именованный контекст для файлов конфигураций.

## Использование контейнера в разных окружениях приложения { #build-container-env }

Окружения для приложений называются в зависимости от их назначения:
Локальное (**dev**) — для разработчика, Тестовое (**test**) — для QA,
Продакшен (**prod**) — для конечных пользователей.

Не используйте [компиляцию контейнера](methods/compile-to-file.md) в среде разработки (_dev_), иначе все изменения, которые вы внесете конфигурацию контейнера в процессе изменения кода приложения через атрибуты, файлы конфигураций и т.п., не будут приняты во внимание. Компиляция контейнера в PHP код происходит только один раз при первом вызове сборки контейнера.

### Как использовать компиляцию в разных средах приложения { #how-to-build-container-env }

Пример изолирования настройки компиляции контейнера в разных средах разработки:

```php
use Kaspi\DiContainer\DiContainerBuilder;

use function getenv;

$builder = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->load(
        '/app/config/base_services.php',
        '/app/config/prod_services.php'
    )
;

// 🚩 окружение для локальной разработки приложения
if ('dev' === getenv('APP_ENV')) {
    $builder->loadOverride('/app/config/dev_services.php');
}

// 🚩 Компилировать контейнер только когда APP_ENV === 'prod' окружении 
if ('prod' === getenv('APP_ENV')) {
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
