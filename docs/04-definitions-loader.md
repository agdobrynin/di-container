# 📂 DefinitionsLoader
Собирает все определения для контейнера зависимостей из разных файлов (_dependency definitions_).

Предусмотрено два режима сборки:

- Перезаписывать определения, более поздний файл будет перезаписывать определение добавленное ранее;
- Отслеживать уникальность определений;

```php
Kaspi\DiContainer\DefinitionsLoader::load(
    bool $overrideDefinitions,
    string ...$file
)
```
- `$overrideDefinitions` если `true` то уже загруженное определение может быть перезаписано если совпадает идентификатор контейнера.
- `$file` файл(ы) описывающие определения для контейнера зависимостей.

🚩 Файл конфигурации должен использовать ключевое слово `return` и возвращать любой
итерируемый тип или функцию обратного вызова которая возвращает значения через
ключевое слово `yield`.


### Короткий пример:
Файлы конфигураций:
- config/base_services.php
    ```php
    use function Kaspi\DiContainer\{diAutowire, diGet};
    
    // Использую массив для объявления определений
    return [
        diAutowire(ReportMaker::class)
            ->bindArguments(
                mailFrom: 'admin.repost@example.com',
                storage: diGet(ReportStorage::class)
            ),
        // other services
    ];
    ```
- config/prod_services.php
    ```php
    use function Kaspi\DiContainer\diAutowire;
    
    // Использую callback функцию и генератор
    return static function (): Generator {
        yield diAutowire(ReportStorage::class)
            ->bindArguments(dir: '/var/reports/');
    
        // ... many other services
        yield diAutowire(ResportGenerator::class);
    }
    ```
- config/dev_services.php
    ```php
    use function Kaspi\DiContainer\diAutowire
    
    // Использую callback функцию и генератор
    return static function (): Generator {
        yield diAutowire(ReportStorage::class)
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

$loader->load(
    // 🚩 Отслеживать уникальность определений
    overrideDefinitions: false,
    __DIR__.'/config/base_services.php',
    __DIR__.'/config/prod_services.php',
);

if ('dev' === \getenv('APP_ENV')) {
    $loader->load(
        // 🚩 Перезаписать определения с тем же идентификатором
        overrideDefinitions: true,
        __DIR__.'/config/dev_services.php'
    );
}

$container = (new DiContainerFactory())->make($loader->definitions());
$container->get(ReportMaker::class); // получение готового объекта
```
