---
outline: [2, 4]
---
# 👷‍♂️ Сборка контейнера зависимостей

## Обзор { #overview }

Комбинация методов класса `DiContainerBuilder` предоставляет гибкую настройку и сборку контейнера зависимостей.

Цепочка вызовов настройки сборки должна заканчиваться методом `DiContainerBuilder::build()` для получения
настроенного контейнера зависимостей.

Собранный контейнер будет предоставлять стандартные методы `get()`, `has()` из спецификации [PSR-11](https://www.php-fig.org/psr/psr-11/),
[метод `call()`](../04-call-method.md) и дополнительный [метод `set()`](set.md) для динамического добавления определений в контейнер.

```php
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;

/**
 * @var DiContainerCallInterface&DiContainerInterface&DiContainerSetterInterface $container 
 */
$container = (new DiContainerBuilder())
    ->build()
;
```

> [!IMPORTANT]
> Валидация конфигурации контейнера провидится в методе `DiContainerBuilder::build()`.
> При некорректной конфигурации будет выброшено исключение `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface`.

> [!IMPORTANT]
> Методы загрузки определений имеют следующий приоритет, чем раньше использован метод загрузки,
> тем приоритетнее добавленные определения со своим идентификатором контейнера.
> Идентификаторы контейнера для определения должны быть уникальными в рамках создаваемого контейнера.
>
> Для некоторых сценариев использования может понадобиться перезапись ранее добавленных определений.
> Для перезаписи используйте методы `DiContainerBuilder::addDefinitionsOverride()`
> и `DiContainerBuilder::loadOverride()`.
>
> Метод `DiContainerBuilder::import()` имеет самый низкий приорите загрузки определений в контейнер.
> Если определение уже было загружено ранее через методы:
> - `DiContainerBuilder::load()`
> - `DiContainerBuilder::loadOverride()`
> - `DiContainerBuilder::addDefinitions()`
> - `DiContainerBuilder::addDefinitionsOverride()`
>
> то импорт класса не будет выполнен. При возникновении конфликта конфигурации при импорте будет выброшено исключение.

## Загрузка из файлов конфигураций { #load-definitions-from-file }

[Конфигурационный файл](configuration_files.md) может возвращать настроенные определения для контейнера как любой `iterable` тип,
настраивать определения через [конфигуратор определений контейнера](../08-definitions-configurator.md) внутри вызываемого типа.


### DiContainerBuilder::load()

Метод загрузки из [файлов конфигураций](configuration_files.md) с отслеживанием уникальности идентификаторов контейнера:

```php
\Kaspi\DiContainer\DiContainerBuilder::load(
    string $file,
    string ...$_
): static;
```
Параметры:
- `$file` – полный путь к файлу конфигурации определений.
- `$_` – полный путь к файлу конфигурации определений.

> [!IMPORTANT]
> При сборке контейнера методом `DiContainerBuilder::build()` при совпадении идентификаторов контейнера будет выброшено исключение.
>

Пример использования:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$builder = new DiContainerBuilder();
// 🚩 Отслеживать уникальность определений
$builder->load('/app/config/base_services.php');
$container = $builder->build();

$container->get(\App\Services\ReportMaker::class); // получение готового объекта
```

Файлы конфигураций:
::: code-group

```php [base_services.php]
// /app/config/base_services.php
use App\Services\ReportMaker;
use App\Storages\ReportStorage;
use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {

    yield diAutowire(ReportMaker::class)
        ->bindArguments(
            storage: diGet(ReportStorage::class)
        ),

    // other services

};
```

:::


> [!TIP]
> Для определения `App\Services\ReportMaker` идентификатор контейнера будет сформирован автоматически через [хелпер функцию `diAutowire()`](../03-php-definition.md#diautowire).


### DiContainerBuilder::loadOverride()

В определённых сценариях требуется перезапись ранее добавленных определений при совпадении идентификаторов контейнера.

Метод загрузки из файлов конфигураций с перезаписью:

```php
\Kaspi\DiContainer\DiContainerBuilder::loadOverride(
    string $file,
    string ...$_
): static;
```

Параметры:
- `$file` – полный путь к файлу конфигурации определений.
- `$_` – полный путь к файлу конфигурации определений.

Пример использования:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$builder = new DiContainerBuilder();

// 🚩 Отслеживать уникальность определений
$builder->load(
    '/app/config/base_services.php',
    '/app/config/prod_services.php',
);

if ('dev' === \getenv('APP_ENV')) {
    // 🚩 Перезаписать ранее загруженные определения
    $builder->loadOverride(
        '/app/config/dev_services.php'
    );
}

$container = $builder->build();

$container->get(\App\Services\ReportMaker::class); // получение готового объекта
```

Файлы конфигураций:
::: code-group

```php [base_services.php]
// /app/config/base_services.php
use App\Services\ReportMaker;
use App\Storages\ReportStorage;
use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {

    diAutowire(ReportMaker::class)
        ->bindArguments(
            storage: diGet(ReportStorage::class)
        ),

    // other services

};
```

```php [prod_services.php]
// /app/config/prod_services.php
use App\Storages\ReportStorage;
use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {

    yield diAutowire(ReportStorage::class)
        ->bindArguments(dir: '/var/storage/');

};
```

```php [dev_services.php]
// /app/config/dev_services.php
use App\Storages\ReportStorage;
use function Kaspi\DiContainer\diAutowire

return static function (): Generator {

    yield diAutowire(ReportStorage::class)
        ->bindArguments(dir: sys_get_temp_dir())
    ;

};
```

:::

> [!TIP]
> Для определения `App\Services\ReportMaker` и `App\Storages\ReportStorage` идентификатор контейнера
> будет сформирован автоматически через [хелпер функцию `diAutowire()`](../03-php-definition.md#diautowire).


## Добавить определения через коллекцию { #load-definitions-from-collection }

Добавляет коллекцию определений в контейнер без файлов конфигурации. Коллекция должна быть представлена любым `iterable` типом.

Коллекция предоставляет идентификатор контейнера как ключ коллекции, а значение определение контейнера.

### DiContainerBuilder::addDefinitions()

Добавляет коллекцию определений в контейнер с отслеживанием уникальности идентификаторов контейнера:

```php
\Kaspi\DiContainer\DiContainerBuilder::addDefinitions(
    iterable $definitions
): static;
```
Параметры:
- `$definitions` – коллекция определений.

> [!IMPORTANT]
> При сборке контейнера методом `DiContainerBuilder::build()` при совпадении идентификаторов контейнера будет выброшено исключение.
>

Пример использования:
```php
use App\Services\Config\{Foo, Qux};
use App\Services\Baz;
use Kaspi\DiContainer\DiContainerBuilder;
use function Kaspi\DiContainer\{diAutowire, diCallable};

$builder = new DiContainerBuilder()
    ->load('/app/config/services.php');


// использование callback функции в качестве коллекции определений
$fnConfigAccessKey = static function (): \Generator {
    yield 'app.access_key' => diCallable([Foo::class, 'accessKey']);  
};

$builder->addDefinitions(($fnConfigAccessKey)()); 

// использование php массива в качестве коллекции определений
$arrConfigBazClass = [
    diAutowire(Baz::class)
        ->bindArguments('value'),
];

$builder->addDefinitions($arrConfigBazClass);
   
$container = $builder->build();
```

> [!TIP]
> Для определения `App\Services\Baz` идентификатор контейнера будет сформирован автоматически через [хелпер функцию `diAutowire()`](../03-php-definition.md#diautowire).


### DiContainerBuilder::addDefinitionsOverride()

В определённых сценариях требуется перезапись ранее добавленные определения при совпадении идентификаторов контейнера.

Добавляет коллекцию определений в контейнер с перезаписью:

```php
\Kaspi\DiContainer\DiContainerBuilder::addDefinitionsOverride(
    iterable $definitions
): static;
```
Параметры:
- `$definitions` – коллекция определений.

Пример использования:
```php
use App\Services\Config\Qux;
use App\Services\Baz;
use Kaspi\DiContainer\DiContainerBuilder;
use function Kaspi\DiContainer\diCallable;

$builder = new DiContainerBuilder()
    ->load('/app/config/services.php');

// ...

if ('test' === \getenv('APP_ENV')) {
    // 🚩 Перезаписать ранее загруженные определения
    // с идентификатором 'app.access_key'
    $builder->addDefinitionsOverride([
        'app.access_key' => diCallable([Qux::class, 'accessKey']);
    ])
}
   
$container = $builder->build();
```

## Регистрация параметров контейнера { #load-parameters }
Конфигурацию параметров контейнера можно представить в виде коллекции
ключ-значение, где ключ это строковое имя параметра, а значение представлено одним из [поддерживаемых типов](../09-container-parameters.md#поддерживаемые-типы-значений-параметров-контейнера).

> [!NOTE]
> Прочитайте главу «[параметры контейнера](../09-container-parameters.md)».

> [!IMPORTANT]
> Все добавленные ранее параметры в конфигурацию могут быть замены новыми значениями если имя параметра совпадает.


### DiContainerBuilder::loadParameters() 

Регистрация параметров контейнера из файлов:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::loadParameters(
    string $file,
    string ...$_
): static
```

Параметры:
- `$file` – полный путь к файлу описывающий конфигурацию параметров.
- `$_` – дополнительные файлы конфигураций параметров контейнера.

### DiContainerBuilder::addParameters() 

Регистрация параметров контейнера из коллекции:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::addParameters(
    iterable $params
): static
```

Параметры:
- `$params` – коллекция ключ-значение конфигурации параметров.

### DiContainerBuilder::setParameter()

Регистрация параметра контейнера:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::setParameter(
    string $name,
    array|int|float|string|bool|null|\UnitEnum $value
): static
```

Параметры:
- `$name` – имя параметра.
- `$value` – значение параметра.

## DiContainerBuilder::import()

Импорт обеспечивает доступность PHP классов и их конфигурирование как определений
в контейнере. Если в конфигурации контейнера
указано [использование PHP атрибутов](../container-config/index.md#use-attribute), то они будут также использованы для
конфигурирования каждого определения.

Загрузка PHP классов из указанных директорий происходит с учётом пространства имён (_namespace_).

Так же импорт будет полезен когда контейнер имеет настройку – [запрещено автоматически разрешать
зависимости класса](../container-config/index.md#use-zero-configuration-definition)
если он явно не объявлен в контейнере.

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
> Классы и интерфейсы (_fully qualified class name_) которые будут найдены в исключённых файлах
> для контейнера недоступны для разрешения.

> [!TIP]
> При необходимости можно удалить из контейнера определение [через конфигуратор](../08-definitions-configurator.md).
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
> Подробное описание и примеры использования описаны в разделе «[Использование контекста для конфигурационных файлов](../08-definitions-configurator.md#использование-контекста-для-конфигурационных-файлов)».

> [!WARNING]
> Значение для ранее добавленных контекстов могут быть
> заменены если имена контекста совпадают.

### DiContainerBuilder::addConfiguratorContexts()

Передача коллекции контекста для конфигурационных файлов:

```php
\Kaspi\DiContainer\Interfaces\DiContainerBuilder::addConfiguratorContexts(
    iterable $contexts
): static
```
Параметры:
- `$contexts` – коллекция ключ-значение, где ключ коллекции это имя контекста;

### DiContainerBuilder::setConfiguratorContext()

Передача одиночного контекста в конфигурационный файл:

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

Не используйте [компиляцию контейнера](#compile-to-file) в среде разработки (_dev_),
иначе все изменения, которые вы внесете в определения (атрибуты, файлы конфигурации и т.д.),
не будут приняты во внимание. Компиляция конечного файла контейнера происходит только один раз и возвращается
всегда экземпляр контейнера сформированного при первой компиляции.

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

