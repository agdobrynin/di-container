# 👷‍♂️ DiContainerBuilder
Комбинация методов класса `DiContainerBuilder` предоставляет гибкую настройку и сборку контейнера зависимостей.

Класс-строитель `\Kaspi\DiContainer\DiContainerBuilder` реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerBuilderInterface` и предоставляет следующие методы:

- [Загрузка из файлов](#загрузка-из-файлов-конфигураций) конфигураций:
  - `DiContainerBuilder::load()`.
  - `DiContainerBuilder::loadOverride()`.
- [Добавить определения](#добавить-определения-через-коллекцию) через метод:
  - `DiContainerBuilder::addDefinitions()`.
  - `DiContainerBuilder::addDefinitionsOverride()`.
- [Регистрация параметров контейнера](#регистрация-параметров-контейнера)
  - `DiContainerBuilder::loadParameters()` – регистрация параметров контейнера из файлов конфигураций.
  - `DiContainerBuilder::addParameters()` – регистрация параметров контейнера из коллекции.
  - `DiContainerBuilder::setParameter()` – регистрация параметра.
- [импорт классов в контейнер](#импорт-классов-в-контейнер):
  - `DiContainerBuilder::import()` – импорт из директорий и их конфигурирование на основе PHP атрибутов.
- [компиляция контейнера](#компиляция-контейнера):
  - `DiContainerBuilder::compileToFile()` – генерация настроенного контейнера в PHP-код оптимизированного специально для вашей конфигурации и ваших классов.
- `DiContainerBuilder::build()` – финальная сборка и получение контейнера 🏁.

Цепочка вызовов настройки сборки должна заканчиваться методом `DiContainerBuilder::build()` для получения
настроенного контейнера зависимостей.

Собранный контейнер будет предоставлять стандартные методы `get()`, `has()` из спецификации [PSR-11](https://www.php-fig.org/psr/psr-11/),
[метод `call()`](03-call-method.md) и дополнительный [метод `set()`](#динамическое-добавление-определений-в-контейнер) для динамического добавления определений в контейнер.

```php
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;

/**
 * @var DiContainerCallInterface & DiContainerInterface & DiContainerSetterInterface $container 
 */
$container = (new \Kaspi\DiContainer\DiContainerBuilder())
    ->build()
;
```
> [!IMPORTANT]
> Валидация конфигурации контейнера провидится в методе `DiContainerBuilder::build()`.
> При некорректной конфигурации будет выброшено исключение `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface`.

> [!NOTE]
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


## Установка индивидуальной конфигурации контейнера.
Для настройки поведения контейнера можно использовать индивидуальную [настройку конфигурации](../README.md#конфигурирование-dicontainer).

Конфигурация по умолчанию:
```php
use Kaspi\DiContainer\DiContainerConfig;

$diConfig = new DiContainerConfig(
    useZeroConfigurationDefinition: true,
    useAttribute: true,
    isSingletonServiceDefault: false,
);
```
При необходимости можно изменить настройки по умолчанию в `DiContainerConfig` и передать конфигурацию
в `DiContainerBuilder`:
```php
use Kaspi\DiContainer\{DiContainerConfig, DiContainerBuilder};

$diConfig = new DiContainerConfig(
    useZeroConfigurationDefinition: false,
    useAttribute: false,
    isSingletonServiceDefault: true,
);

// передать настройки в построитель контейнера
$container = (new DiContainerBuilder(containerConfig: $diConfig))
    ->build()
;
```

## Загрузка из файлов конфигураций.
Загрузка из отдельных файлов конфигураций для конфигурирования определений контейнера.

Конфигурационный файл может возвращать настроенные определения для контейнера либо использовать
вызов callback функции для конфигурирования через параметр [конфигуратор определений контейнера](08-definitions-configurator.md).

Файл конфигурации с возвращаемыми определениями:
```php
// /app/config/services.php
use function Kaspi\DiContainer\diAutowire;

return static function (): \Generator {

    yield diAutowire(Foo::class)
        ->bindArguments('baz val');

};
```
Комбинирование возвращаемых определений и [конфигуратора](08-definitions-configurator.md):
```php
// /app/config/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {
    $configurator->removeDefinition(Baz::class);

    yield diAutowire(Foo::class)
        ->bindArguments('baz val');

};
```
Конфигурационный файл без возвращаемого типа с использованием только [конфигуратора определений](08-definitions-configurator.md):
```php
// /app/config/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $configurator->removeDefinition(Baz::class);
    $configurator->setParameter('adminEmail', 'admin@example.com');
    
    $configurator->setDefinition(
        Foo::class,
        diAutowire(Foo::class)
            ->bindTag('tags.foo_app');
    );
};
```

> [!IMPORTANT]
> Файл конфигурации должен использовать ключевое слово `return`.
 
> [!NOTE]
> Файл конфигурации может возвращать любой итерируемый тип. Например:
> - Функцию с возвращаемым типом `\Generator`.
> - простой php массив `[]`.
> - любое `callable` выражение с возвращаемым типом `iterable`.

> [!TIP]
> Использование для конфигурационных файлов возвращаемого типа `\Generator` позволяет оптимизировать создание определений в контейнере
> и рекомендован для конфигурирования контейнера.

> [!TIP]
> Для некоторых определений идентификатор контейнера может быть сформирован автоматически.
> - [хелпер функция `diAutowire()`](01-php-definition.md#diautowire)
> - [хелпер функция `diRuntime()`](10-runtime-definition.md#diruntime)
> - [PHP атрибут `#[Autowire()]`](02-attribute-definition.md#autowire)
>

В рамках создаваемого контейнера отслеживается уникальность идентификаторов определений.
Если вновь загружаемый идентификатор контейнера уже присутствует, то будет выброшено исключение
`\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface` при вызове
метода сборки контейнера `DiContainerBuilder::build()`.

#### Метод загрузки из файлов конфигураций с отслеживанием уникальности идентификаторов контейнера:
```php
\Kaspi\DiContainer\DiContainerBuilder::load(string $file, string ...$_): static;
```
Параметры:
- `$file` – полный путь к файлу конфигурации определений;
- `$_` – полный путь к файлу конфигурации определений;

> [!IMPORTANT]
> При сборке контейнера методом `DiContainerBuilder::build()` при совпадении идентификаторов контейнера будет выброшено исключение.
>

В определённых сценариях требуется перезапись ранее добавленных определений при совпадении идентификаторов контейнера.

#### Метод загрузки из файлов конфигураций с перезаписью:
```php
\Kaspi\DiContainer\DiContainerBuilder::loadOverride(string $file, string ...$_): static;
```
Параметры:
- `$file` – полный путь к файлу конфигурации определений;
- `$_` – полный путь к файлу конфигурации определений;

#### Пример использования.

Файлы конфигураций:
- /app/config/parameters.php
    ```php
    return [
        'emails.report_from' => 'admin.repost@example.com',
        'storage.report_dir' => '/var/reports/',
    ];
    ```
- /app/config/base_services.php
    ```php
    use App\Services\ReportMaker;
    use App\Storages\ReportStorage;
    use function Kaspi\DiContainer\{diAutowire, diGet, diParameter};
    
    return static function (): Generator {
  
        diAutowire(ReportMaker::class)
            ->bindArguments(
                mailFrom: diParameter('emails.report_from'),
                storage: diGet(ReportStorage::class)
            ),
  
        // other services

    };
    ```
- /app/config/prod_services.php
    ```php
    use App\Services\ResportGenerator;
    use App\Storages\ReportStorage;
    use function Kaspi\DiContainer\{diAutowire, diParameter};
    
    return static function (): Generator {
  
        yield diAutowire(ReportStorage::class)
            ->bindArguments(dir: diParameter('storage.report_dir'));
    
        // ... many other services
        yield diAutowire(ResportGenerator::class);
  
    };
    ```
- /app/config/dev_services.php
    ```php
    use App\Storages\ReportStorage;
    use function Kaspi\DiContainer\diAutowire
    
    return static function (): Generator {
  
        yield diAutowire(ReportStorage::class)
            ->bindArguments(dir: sys_get_temp_dir())
        ;
  
        // ... many other services
  
    };
    ```
Сборка контейнера:
```php
$builder = new \Kaspi\DiContainer\DiContainerBuilder();

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

## Добавить определения через коллекцию.
Добавляет коллекцию определений в контейнер. Предусмотрено два варианта:
- отслеживать уникальные идентификаторы у добавляемых определений.
- перезапись ранее добавленных определения с совпадающими идентификаторов контейнера.

#### Метод с отслеживанием уникальности идентификаторов контейнера:
```php
\Kaspi\DiContainer\DiContainerBuilder::addDefinitions(iterable $definitions): static;
```
Параметры:
- `$definitions` – коллекция определений;

> [!IMPORTANT]
> При сборке контейнера методом `DiContainerBuilder::build()` при совпадении идентификаторов контейнера будет выброшено исключение.
>

#### Добавить определения контейнера с перезаписью:
В определённых сценариях требуется перезапись ранее добавленные определения при совпадении идентификаторов контейнера.
```php
\Kaspi\DiContainer\DiContainerBuilder::addDefinitionsOverride(iterable $definitions): static;
```
Параметры:
- `$definitions` – коллекция определений;

Пример использования:
```php
use App\Services\Config\{Foo, Qux};
use App\Services\Baz;

$builder = new \Kaspi\DiContainer\DiContainerBuilder()
    ->load('/app/config/services.php');

// ...

    // использование callback функции в качестве коллекции определений
    $builder->addDefinitions((static function () {
        yield 'app.access_key' => \Kaspi\DiContainer\diCallable([Foo::class, 'accessKey']);  
    })())
;

// ...

    // использование php массива в качестве коллекции определений
    $builder->addDefinitions([
        \Kaspi\DiContainer\diAutowire(Baz::class)
            ->bindArguments('value'),
    ])
;

// ...

if ('test' === \getenv('APP_ENV')) {
    // 🚩 Перезаписать ранее загруженные определения
    // с идентификатором 'app.access_key'
    $builder->addDefinitionsOverride([
        'app.access_key' => \Kaspi\DiContainer\diCallable([Qux::class, 'accessKey']);
    ])
}
   
$container = $builder->build();
```

## Регистрация параметров контейнера.
Конфигурацию параметров контейнера можно представить в виде коллекции
ключ-значение, где ключ это строковое имя параметра, а значение представлено одним из [поддерживаемых типов](09-container-parameters.md#поддерживаемые-типы-значений-параметров-контейнера).

Прочитайте главу «[параметры контейнера](09-container-parameters.md)».

### Регистрация параметров контейнера из файлов.
```php
DiContainerBuilder::loadParameters(string $file, string ...$_): static
```
Параметры:
- `$file` – полный путь к файлу описывающий конфигурацию параметров.
- `$_` – дополнительные файлы конфигураций параметров контейнера.

### Регистрация параметров контейнера из коллекции.
```php
DiContainerBuilder::addParameters(iterable $params): static
```
Параметры:
- `$params` – коллекция ключ-значение конфигурации параметров.

### Регистрация параметра контейнера.
```php
DiContainerBuilder::setParameter(string $name, array|int|float|string|bool|null|\UnitEnum $value): static
```
Параметры:
- `$name` – имя параметра.
- `$value` – значение параметра.

## Импорт классов в контейнер.
Импорт обеспечивает доступность классов и их конфигурирование как определений
в контейнере. Если [в конфигурации контейнера](../README.md#конфигурирование-dicontainer)
указано использование PHP атрибутов (`$useAttribute = true`) то они будут также использованы для
конфигурирования каждого определения.

Загрузка классов из указанных директорий происходит с учётом пространства имён (_namespace_).

Так же импорт будет полезен когда контейнер имеет настройку
`$useZeroConfigurationDefinition = false` – [запрещено автоматически разрешать
зависимости класса](../README.md#конфигурирование-dicontainer)
если он явно не объявлен в контейнере.

**Импорт классов:**
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
  импортировать классы (_например: `'App\\'` – загружать если namespace класса начинается с префикса_)
- `$src` – директория из которой импортировать классы;
- `$excludeFiles` – исключить из загрузки файлы по шаблону;
- `$availableExtensions` – указать расширения у файлов которые будут обработаны;

> [!NOTE]
> Параметр `$excludeFiles` использует синтаксис шаблонов из [php функции `\fnmatch()`](https://www.php.net/manual/en/function.fnmatch.php).
>
> Классы и интерфейсы (_fully qualified class name_) которые будут найдены в исключённых файлах
> для контейнера недоступны для разрешения.

> [!TIP]
> **Удаляемы определения**. При необходимости можно удалить
> из контейнера определение [через конфигуратор](08-definitions-configurator.md). Это полезно, например, для того, чтобы сделать сервис недоступным при определенных сценариях использования контейнера.
>

> [!TIP]
> Импорт может быть выполнен из нескольких директорий если это необходимо.
> В случае импорта из нескольких источников следует помнить что параметр `$namespace`
> должен быть уникальным:
> ```php
> use Kaspi\DiContainer\DiContainerBuilder;
> 
> $builder = (new DiContainerBuilder())
>   ->import(namespace: 'App\\Services\\', src: '/app/src/Services')
>   ->import(namespace: 'App\\Actions\\', src: '/app/src/Actions')
> ;
> ```

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

## Компиляция контейнера.
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
- `$outputDirectory` – директория в файловой системе для скомпилированного контейнера;
- `$containerClass` – имя класса для скомпилированного контейнера включая пространство имен класса (fully qualified class name);
- `$permissionCompiledContainerFile` – права доступа к файлу в который будет сохранен скомпилированный PHP-код;
- `$isExclusiveLockFile` – эксклюзивная блокировка файла во время записи PHP-кода в конечный файл;
- `$options` – дополнительные [настройки компилятора](#дополнительные-настройки-компилятора);

> [!IMPORTANT]
> Для обеспечения максимальной производительности компиляция контейнера происходит один раз если конечный файл
> содержащий PHP-код не найден.
> При повторном вызове сборки контейнера с компиляцией если будет найден ранее сгенерированный файл,
> то повторная компиляция будет пропущена.
> При развёртывании новых версий контейнера (измененных) в продуктивной среде (_prod env_)
> вы должны удалить сгенерированный файл (или каталог, который его содержит), чтобы обеспечить повторную компиляцию контейнера.

> [!IMPORTANT]
> Имя файла для скомпилированного контейнера генерируется на основании параметров `$outputDirectory` и `$containerClass`.
> Сформированное полное имя файла это директория назначения `$outputDirectory` плюс имя класс из `$containerClass` без учёта namespace указанного класса. 

#### Пример настройки компиляции контейнера:
```php
$builder = new \Kaspi\DiContainer\DiContainerBuilder();

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

### Дополнительные настройки компилятора.
Настройки передаются в виде ассоциативного массива со значениями:
1. `'invalid_behavior'` – принимает тип `\Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum`, значение по умолчанию
   `\Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum::ExceptionOnCompile`;
2. `'di_definition_transformer'` – принимает тип `\Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface`,
значение по умолчанию пусто;
3. `'compiled_entries'` – принимает тип `\Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface`,
значение по умолчанию пусто;
4. `'force_rebuild'` – принимает тип `bool`, значение по умолчанию `false`;

## Использование контейнера в разных окружениях приложения.
Окружения для приложений называются в зависимости от их назначения:
Локальное (**dev**) — для разработчика, Тестовое (**test**) — для QA,
Продакшен (**prod**) — для конечных пользователей.

Не используйте [компиляцию контейнера](#компиляция-контейнера) в среде разработки (_dev_),
иначе все изменения, которые вы внесете в определения (атрибуты, файлы конфигурации и т.д.),
не будут приняты во внимание. Компиляция конечного файла контейнера происходит только один раз и возвращается
всегда экземпляр контейнера сформированного при первой компиляции.

Как использовать компиляцию при разных средах приложения:
```php
$builder = (new \Kaspi\DiContainer\DiContainerBuilder())
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

## Динамическое добавление определений в контейнер.
Прямая установка определений в уже сформированный контейнер зависимостей:
```php
\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface::set(string $id, mixed $definition): static;
```
Параметры:
- `$id` – идентификатор контейнера, непустая строка;
- `$definition` – определение соответствующее идентификатору `$id`;

> [!NOTE] 
> Если идентификатор контейнера не уникален в рамках текущего контейнера, то будет выброшено исключение `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface`.

Определение может быть представлено ввиде [хелпер функции](01-php-definition.md#объявления-для-определений-контейнера) или
объекта.

> [!WARNING]
> Рекомендуется использовать [файлы конфигурации](#загрузка-из-файлов-конфигураций),
> [добавлять определения из коллекции](#добавить-определения-через-коллекцию)
> или [импортировать классы и интерфейсы](#импорт-классов-в-контейнер)
> через класс-строитель `DiContainerBuilder`,
> так как определения установленные напрямую в контейнер не будут [скомпилированы для продуктивной среды](#компиляция-контейнера) (_prod env_).
> 
> В некоторых сценариях при использовании метода `set()` необходимо отслеживать чтобы получение сервиса
> через метод контейнера `get()` не вызывало ошибки из-за того что определение ещё необавлено в контейнер.
> 

#### Пример использования:
```php
// app/src/Services/Others.php
// ⚠️ Класс который нужно конфигурировать отдельно.
namespace App\Services;

use App\Services\Others;

final class Others {
    public function __construct(
        // some dependencies
    ) {}
}
```
```php
// app/src/Services/Foo.php
namespace App\Services;

use App\Services\Others;

final class Foo {
    public function __construct(public readonly Others $others) {}
}
```
```php
// конфигурация и получение готового контейнера зависимостей
$container = (new \Kaspi\DiContainer\DiContainerBuilder())
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
> [!WARNING]
> Установка в контейнер нового определения должно быть до вызова метода контейнера `get()`
> который может разрешить зависимость `'App\\Services\\Others'`.

Вариант установки объекта в контейнер:
```php
use App\Services\Others;

$others = new Others(
    // set some dependencies.
);

// идентификатор будет указан как 'App\\Services\\Others'
$container->set($others::class, $others);
```
Вариант конфигурирования класса через хелпер-функцию:
```php
use App\Services\Others;
use function Kaspi\DiContainer\diAutowire;

// идентификатор будет указан как 'App\\Services\\Others'
$container->set(
    Others::class,
    diAutowire(Others::class)
        ->bindTag('tags.other')
        ->bindArguments(
            // передача аргументов конструктору класса.
        )
);
```
```php
use App\Services\Foo;

$foo = $container->get(Foo::class);

var_dump($foo->others instanceof App\Services\Others);
// `true`
```
