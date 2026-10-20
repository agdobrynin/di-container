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

🚧🚧🚧🚧🚧🚧🚧🚧


## DiContainerBuilder::import()

Импорт обеспечивает объявление PHP классов в контейнере. Если в конфигурации контейнера указано [использование PHP атрибутов](../container-config/index.md#use-attribute), то они будут также использованы объявления PHP классов.

Загрузка PHP классов из указанных директорий происходит с учётом пространства имён (_namespace_).

Так же импорт будет полезен когда контейнер имеет настройку – [запрещено автоматически внедрять зависимости класса](../container-config/index.md#use-zero-configuration-definition) если он явно не объявлен в контейнере.

Импорт классов:

```php
\Kaspi\DiContainer\DiContainerBuilder::import(
    string $namespace,
    string $src,
    array $excludeFiles = [],
    array $availableExtensions = ['php'],
): static;
```

Параметры:

- `$namespace` – префикс пространства имён из которого следует
  импортировать классы (_например: `'App\\'` – загружать если namespace класса начинается с префикса_).
- `$src` – директория из которой импортировать классы.
- `$excludeFiles` – исключить из загрузки файлы по шаблону.
- `$availableExtensions` – указать расширения у файлов которые будут обработаны.

> [!NOTE]
> Параметр `$excludeFiles` использует синтаксис шаблонов из [php функции `\fnmatch()`](https://www.php.net/manual/en/function.fnmatch.php).
>
> FQCN [^FQCN] которые будут найдены в исключённых файлах для контейнера недоступны при внедрении зависимостей.

> [!TIP]
> При необходимости можно удалить из контейнера определение [через конфигуратор](definitions-configurator/index.md).
> Это полезно, например, для того, чтобы сделать сервис недоступным при определенных сценариях использования контейнера.
>

Пример использования:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$builder = (new DiContainerBuilder())
    ->import(
        namespace: 'App\\',
        src: '/app/src/',
        excludeFiles: [
            '/app/src/Events/*',
            '/app/src/*Kernel.php',
            '/app/src/Container.php',
        ]
    )
    // 🚩 Отслеживать уникальность определений
    ->load(
        '/app/config/base_services.php',
        '/app/config/prod_services.php',
    )
;

if ('dev' === \getenv('APP_ENV')) {

    $builder->loadOverride('/app/config/dev_services.php');

}

$container = $builder->build();
```


Импорт может быть выполнен из нескольких директорий если это необходимо.
В случае импорта из нескольких источников следует помнить что параметр `$namespace` должен быть уникальным:
```php
use Kaspi\DiContainer\DiContainerBuilder;
 
$builder = (new DiContainerBuilder())
  ->import(namespace: 'App\\Services\\', src: '/app/src/Services')
  ->import(namespace: 'App\\Actions\\', src: '/app/src/Actions')
;
```

## DiContainerBuilder::compileToFile() { #compile-to-file }

Для повышения производительности контейнера зависимостей реализован компилятор который преобразует
настроенный контейнер в готовый к использованию PHP-код сохраняемый в файл,
чтобы при следующих запусках контейнер загружался мгновенно,
минуя этап парсинга конфигурации, что значительно повышает производительность.

```php
\Kaspi\DiContainer\DiContainerBuilder::compileToFile(
    string $outputDirectory,
    string $containerClass,
    int $permissionCompiledContainerFile = 0666,
    bool $isExclusiveLockFile = true,
    array $options = []
): static;
```

Параметры:
- `$outputDirectory` – директория в файловой системе для скомпилированного контейнера.
- `$containerClass` – имя класса для скомпилированного контейнера включая пространство имен класса (fully qualified class name).
- `$permissionCompiledContainerFile` – права доступа к файлу в который будет сохранен скомпилированный PHP-код.
- `$isExclusiveLockFile` – эксклюзивная блокировка файла во время записи PHP-кода в конечный файл.
- `$options` – дополнительные [настройки компилятора](#compile-options).

> [!IMPORTANT]
> Для обеспечения максимальной производительности компиляция контейнера происходит один раз если конечный файл
> содержащий PHP-код не найден.
> При повторном вызове сборки контейнера с компиляцией если будет найден ранее сгенерированный файл,
> то повторная компиляция будет пропущена.
> При развёртывании новых версий контейнера (измененных) в продуктивной среде (_prod env_)
> вы должны удалить сгенерированный файл (или каталог, который его содержит), чтобы обеспечить повторную компиляцию контейнера.

> [!TIP]
> Имя файла для скомпилированного контейнера генерируется на основании параметров `$outputDirectory` и `$containerClass`.
> Сформированное полное имя файла это директория назначения `$outputDirectory` плюс имя класс из `$containerClass` без учёта namespace указанного класса.

Пример настройки компиляции контейнера:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$builder = new DiContainerBuilder();

// ...

$builder->compileToFile(
    outputDirectory: '/app/var/container',
    // Имя класса с указанием пространства имен для компилируемого контейнера
    containerClass: 'App\\Core\\FooContainer',
);

$container = $builder->build();
```

> [!TIP]
> Будет сформирован файл `/app/var/container/FooContainer.php`.
>

> [!WARNING]
> Директория указанная в параметре `$outputDirectory` должна существовать и быть доступна для чтения и записи.
>

### Дополнительные настройки компилятора { #compile-options }

Настройки передаются в виде ассоциативного массива со значениями:
* `'invalid_behavior'` – принимает тип `\Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum`, значение по умолчанию
   `\Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum::ExceptionOnCompile`.
* `'di_definition_transformer'` – принимает тип `\Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface`
   значение по умолчанию пусто.
* `'compiled_entries'` – принимает тип `\Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface`,
   значение по умолчанию пусто.
* `'force_rebuild'` – принимает тип `bool`, значение по умолчанию `false`.

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
