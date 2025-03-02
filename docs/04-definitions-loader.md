# 📂 DefinitionsLoader
Собирает все определения для контейнера зависимостей из разных конфигурационных файлов (_dependency definitions_).
Так же доступен "импорт" классов в контейнер из директорий.

## Загрузка из конфигурационных файлов.
Предусмотрено два режима загрузки:

- Отслеживать уникальность определений:
  ```php
  Kaspi\DiContainer\DefinitionsLoader::load(
      string ...$file
  )
  ```
  Аргументы:
    - `$file` – файл(ы) описывающие определения для контейнера зависимостей.

- Перезаписывать определения, будет перезаписывать определение добавленное ранее:
  ```php
  Kaspi\DiContainer\DefinitionsLoader::loadOverride(
      string ...$file
  )
  ```
  Аргументы:
  - `$file` – файл(ы) описывающие определения для контейнера зависимостей.

> [!WARNING]
> Файл конфигурации должен использовать ключевое слово `return`
> и возвращать любой итерируемый тип
> или функцию обратного вызова которая возвращает значения через ключевое слово `yield`.


### Короткий пример:
Файлы конфигураций:
- config/base_services.php
    ```php
    use function Kaspi\DiContainer\{diAutowire, diGet};
    
    // Использую массив для объявления определений
    return [
  
        diAutowire(App\Services\ReportMaker::class)
            ->bindArguments(
                mailFrom: 'admin.repost@example.com',
                storage: diGet(App\Storages\ReportStorage::class)
            ),
  
        // other services

    ];
    ```
- config/prod_services.php
    ```php
    use function Kaspi\DiContainer\diAutowire;
    
    // Использую callback функцию и генератор
    return static function (): Generator {
  
        yield diAutowire(App\Storages\ReportStorage::class)
            ->bindArguments(dir: '/var/reports/');
    
        // ... many other services
        yield diAutowire(App\Services\ResportGenerator::class);
  
    }
    ```
- config/dev_services.php
    ```php
    use function Kaspi\DiContainer\diAutowire
    
    // Использую callback функцию и генератор
    return static function (): Generator {
  
        yield diAutowire(App\Storages\ReportStorage::class)
            ->bindArguments(dir: sys_get_temp_dir())
        ;
  
        // ... many other services
  
    }
    ```
Загрузка определений в контейнер:
```php
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainerFactory;

$loader = new DefinitionsLoader();

// 🚩 Отслеживать уникальность определений
$loader->load(

    __DIR__.'/config/base_services.php',

    __DIR__.'/config/prod_services.php'

);

if ('dev' === \getenv('APP_ENV')) {
    // 🚩 Перезаписать определения
    $loader->loadOverride(
    
        __DIR__.'/config/dev_services.php'
    
    );
}

$container = (new DiContainerFactory())->make($loader->definitions());
$container->get(App\Services\ReportMaker::class); // получение готового объекта
```

## Импорт классов из директорий.
Обеспечивает доступность классов как определений
в контейнере (_[через `diAutowire`](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#diautowire)_).
Загрузка классов из указанных директорий происходит с учётом пространства имен.

Такая опция будет полезна когда используюется конфигурирование через php-атрибуты,
в частности для [тегированных классов](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md)
([#[Tag]](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#tag)).

Так же такой способ "загрузки" будет полезен когда контейнер имеет настройку
`$useZeroConfigurationDefinition = false` – [запрещено автоматически разрешать
зависимости класса](https://github.com/agdobrynin/di-container/tree/main?tab=readme-ov-file#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer)
если он явно не объявлен в контейнере.

**Метод импорта:**
```php
Kaspi\DiContainer\DefinitionsLoader::import(
    string $namespace,
    string $src,
    array $excludeFilesRegExpPattern = [],
    array $availableExtensions = ['php']
)
```
Аргументы:
- `$namespace` – префикс пространства имен из которого следует
загрузить класс (_например: `'App\\'` – загружать классы начинающихся с префикса_);
- `$src` – директория из которой загружать классы;
- `$excludeFilesRegExpPattern` – исключить из загрузки файлы по регулярному выражению;
- `$availableExtensions` – указать какие расширения у файла должны быть;

> [!TIP]
> Загрузка может быть выполнена из нескольких директорий если это необходимо.

Пример использования `import`:

```php
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainerFactory;

$loader = new DefinitionsLoader();

// 🚩 Отслеживать уникальность определений
$loader->load(

    __DIR__.'/config/base_services.php',

    __DIR__.'/config/prod_services.php'

);

if ('dev' === \getenv('APP_ENV')) {

    $loader->loadOverride(__DIR__.'/config/dev_services.php');

}

$loader->import(
    namespace: 'App\\',
    src: __DIR__.'/../src/',
    excludeFilesRegExpPattern: [
        '#/src/Events/#',
        '#/src/config/.+\.php$#',
        '#src/(Kernel|Container)\.php$#',
    ]
)

$container = (new DiContainerFactory())->make(
    $loader->definitions()
);
```
