# #️⃣ DiContainer c конфигурированием через PHP атрибуты { #title }

## Обзор { #overview }

[В конфигурации контейнера](../container-config/index.md#use-attribute) по умолчанию параметр `$useAttribute` включён.

Для указания контейнеру каким образом нужно внедрять зависимости в PHP классах или вызываемых типах используется механизм конфигурирования через PHP атрибуты.

Для создания настроенного контейнера используется класс-строитель [DiContainerBuilder](../container-builder/index.md).

PHP атрибуты содержат мета-данные для конфигурирования определений контейнера – передать аргументы для разных типов, указать дополнительное конфигурирование через сеттер методы или добавить теги.

При конфигурировании контейнера можно совмещать PHP атрибуты и PHP определения.

> [!WARNING]
> PHP атрибуты имеют более высокий приоритет при конфигурировании чем [конфигурация в стиле PHP определений](../php-definition/index.md).
>

> [!IMPORTANT]
> Для каждого идентификатора контейнера и его определения необходимо выбрать только один способ конфигурации – через php атрибуты или через [файлы-конфигураций](../container-builder/configuration_files.md). 

### Доступные атрибуты { #attributes }
- [Autowire](autowire.md) – конфигурирование PHP класса или их набора в контейнере.
- [AutowireExclude](autowire-exclude.md) – исключить внедрение PHP класса или интерфейса.
- [Setup](setup.md) – внедрение зависимости в PHP класс через мутабельный сеттер-метод.
- [SetupImmutable](setup-immutable.md) – внедрение зависимости в PHP класс через иммутабельный сеттер-метод.
- [SetupPriority](setup-priority.md) – приоритет сеттер-метода.
- [Inject](inject.md) – внедрение зависимости через идентификатор контейнера.
- [InjectByCallable](inject-by-callable.md) – внедрение зависимости через вызываемый тип.
- [Service](#service) – определение для интерфейса какой PHP класс будет вызван и разрешен в контейнере.
- [DiFactory](#difactory) – разрешение зависимости с помощью класса-фабрики.
- [ProxyClosure](#proxyclosure) – внедрение зависимости в параметры конструктора PHP класса, метода или аргументов функции с отложенной инициализацией через класс `\Closure`, анонимную функцию.
- [Tag](tag.md) – определение тегов для класса.
- [TaggedAs](#taggedas) – внедрение тегированных определений в параметры конструктора, метода PHP класса.
- [Parameter](#parameter) – разрешение зависимости через «параметр контейнера».
- [ParameterRuntime](#parameterruntime) – разрешение зависимости через «параметр контейнера времени исполнения».
- [Параметр переменной длины](#параметр-переменной-длины) – особенности применения атрибутов.

-----

## Service

Применяется к интерфейсу для конфигурирования реализации php интерфейса.
```php
#[Service(string $id, ?bool $isSingleton = null)]
```
Параметры:
- `$id` - класс реализующий интерфейс (FQCN) или идентификатор контейнера.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение null, то значение будет выбрано на основе [настройки контейнера](../README.md#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

> [!NOTE]
> **FQCN** – Fully Qualified Class Name.

```php
// src/Loggers/CustomLoggerInterface.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Service;

#[Service(CustomLogger::class)] // класс реализующий данный интерфейс.
interface CustomLoggerInterface {
    public function loggerFile(): string;
}
```

```php
// src/Loggers/CustomLogger.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;

#[Autowire(
    arguments: [
        'file' => new DiParameter('logger.file_name')    
    ]
)]
class CustomLogger implements CustomLoggerInterface {

    public function __construct(
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}
```
```php
// src/Loggers/MyLogger.php
namespace App\Loggers;

class MyLogger {

    public function __construct(
        // Контейнер найдёт интерфейс
        // и проверит у него php-атрибут Service.
        public CustomLoggerInterface $customLogger
    ) {}
}
```

```php
// config/parameters.php
return [
    'logger.file_name' => '/var/log/app.log'
];
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters(__DIR__.'/config/parameters.php')
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\Loggers\MyLogger::class);

print $myClass->customLogger->loggerFile(); // /var/log/app.log
```

Так же атрибут **Service** можно использовать со ссылкой на другой идентификатор контейнера.

```php
// src/Loggers/CustomLoggerInterface.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Service;

#[Service('services.app_logger')]
interface CustomLoggerInterface {
    public function loggerFile(): string;
}
```
```php
// src/Loggers/CustomLogger.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;

#[Autowire(
    id: 'services.app_logger',
    arguments: [
    'file' => new DiParameter('logger.file_name')    
    ]
)]
class CustomLogger implements CustomLoggerInterface {

    public function __construct(
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}
```
## DiFactory
Атрибут может применяться к классу или к параметру функции, метода.

Сигнатура php атрибута:
```php
#[DiFactory(string|array $definition, ?bool $isSingleton = null, array $arguments = [])]
```
Параметры:
- `$definition` – представление php класса и метода фабрики.
- `$isSingleton` – возвращать один и тот же результат (паттерн singleton). Если значение null, то значение будет выбрано на основе [настройки контейнера](../README.md#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).
- `$arguments` – предать аргументы для метода фабрики.

> [!NOTE]
> Значение указанное в `\Kaspi\DiContainer\Attributes\DiFactory::$isSingleton` при применении к параметрам метода (функции) будет проигнорирован
> и не используется при разрешении зависимостей.

> [!NOTE]
> Подробное [описание работы с фабриками](07-factory.md) для разрешения зависимостей в контейнере.

## ProxyClosure

Реализация ленивой инициализации параметров класса (зависимости) через PHP класс `\Closure`.
Применяется к параметрам конструктора класса, метода или функции.

```php
#[ProxyClosure(string $containerIdentifier)]
```
Параметры:
- `$containerIdentifier` - идентификатора контейнера (php класс, интерфейс) возвращающий результат который необходимо получить отложено.

Такое объявление пригодится для «тяжёлых» зависимостей, требующих длительного времени инициализации или ресурсоёмких вычислений.

> [!TIP]
> Подробное объяснение использования [ProxyClosure](01-php-definition.md#diproxyclosure)

Пример для отложенного получения результата через атрибут `#[ProxyClosure]`:

```php
// src/Services/HeavyDependency.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\ProxyClosure;

/**
 * Класс с «тяжёлыми» зависимостями,
 * много ресурсов на инициализацию.
 */
class HeavyDependency {
    public function __construct(...) {}
    public function doMake() {}
}
```
```php
// src/Classes/ClassWithHeavyDependency.php
namespace App\Classes;

use App\Services\HeavyDependency;
use Kaspi\DiContainer\Attributes\ProxyClosure;

class ClassWithHeavyDependency {
    /**
     * 🚩 Подсказка для IDE при авто-дополении (autocomplete).
     * @param Closure(): HeavyDependency $heavyDependency
     */
    public function __construct(
        #[ProxyClosure(HeavyDependency::class)]
        private \Closure $heavyDependency,
        private LiteDependency $liteDependency,
    ) {}
    
    public function doHeavyDependency() {
        ($this->heavyDependency)()->doMake();
    }
    
    public function doLiteDependency() {
        $this->liteDependency->doMakeLite();
    }
}
```
> [!TIP]
> Для подсказок IDE autocomplete используйте
> PhpDocBlock над конструктором:
> `@param Closure(): HeavyDependency $heavyDependency`

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

$classWithHeavyDependency = $container->get(App\Classes\ClassWithHeavyDependency::class);

$classWithHeavyDependency->doHeavyDependency();
```
> [!NOTE]
> При разрешении зависимости контейнера `App\Classes\ClassWithHeavyDependency::class`
> свойство в классе `ClassWithHeavyDependency::$heavyDependency` ещё не инициализировано.
> Инициализация произойдёт (_разрешение зависимости_) только
> в момент обращения к этому свойству – в частности при вызове
> метода `$classWithHeavyDependency->doHeavyDependency()`.

> [!TIP]
> Если используется PHP 8.4 и выше, то предпочтительно использовать [конфигурирование
> «ленивых объектов»](14-lazy-injection.md) вместо атрибута `ProxyClosure`.


## Tag
Применятся к классу для тегирования.
```php
#[Tag(string $name, array $options = [], int|null|string $priority = null, ?string $priorityMethod = null)]
```
Параметры:
- `$name` - имя тега.
- `$options` - метаданные для тега.
- `$priority` - приоритет для сортировки в коллекции тегов.
- `$priorityMethod` - метод класса для сортировки в коллекции тегов если неуказан `priority`.

> [!IMPORTANT]
> Метод указанный в `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod` должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В качестве аргументов метод принимает два необязательных параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

> [!TIP]
> [Информация о сортировке по приоритету](05-tags.md#%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)
> для параметров `\Kaspi\DiContainer\Attributes\Tag::$priority`, `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod`.

Можно указать несколько атрибутов для PHP класса:
```php
use Kaspi\DiContainer\Attributes\Tag; 
namespace App\Any;

#[Tag(name: 'tags.services.group-one', priorityMethod: 'getPriority')]
#[Tag(name: 'tags.services.group-two', priority: 1000)]
class SomeClass {}
```

> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

## TaggedAs
Получение коллекции (_списка_) сервисов и определений отмеченных тегом.
Применяется к параметрам конструктора класса, метода или функции.
Тегирование класса в стиле php определенй через метод `bindTag` у [хелпер функций](01-php-definition.md#%D0%BE%D0%B1%D1%8A%D1%8F%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-%D1%85%D1%8D%D0%BB%D0%BF%D0%B5%D1%80-%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B8)
или через [php атрибут `#[Tag]`](#tag) у тегированного класса.

Результат выполнения может быть применен для параметров с типом:
- `iterable`
    - `\Traversable`
        - `\Iterator`
- `\ArrayAccess`
- `\Psr\Container\ContainerInterface`
- `array` требуется использовать параметр `$isLazy = false`.
- Составной тип (_intersection types) для ленивых коллекций (`$isLazy = true`)
    - `\ArrayAccess&\Iterator&\Psr\Container\ContainerInterface`.

```php
#[TaggedAs(
    string $name,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
)]
```
Параметры:
- `$name` – имя тега на сервисах которые нужно собрать из контейнера.
- `$isLazy` – получать сервисы только во время обращения или сразу всё.
- `$priorityDefaultMethod` – если получаемый сервис является php классом
  и у него не определен `priority` или `priorityMethod`, то будет выполнена попытка
  получить значение `priority` через вызов указанного метода.
- `$useKeys` – использовать именованные строковые ключи в коллекции.
  По умолчанию в качестве ключа элемента в коллекции используется идентификатор
  определения в контейнере (_container identifier_).
- `$key` – использовать ключ в коллекции для элемента из опций тега (_метаданные из `$options` определенные у тега_).
- `$keyDefaultMethod` – если получаемый сервис является php классом
  и у него не определен `$key`, то будет выполнена попытка
  получить значение ключа тега через вызов указанного метода.
- `$containerIdExclude` – исключить из коллекции определения
  с указанными идентификаторами (_container identifier_).
- `$selfExclude` – исключить из коллекции php класс, в который собирается коллекция
  если он отмечен тем-же тегом, что и получаемая коллекция.

1. Подробнее [о приоритизации в коллекции.](05-tags.md#%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)
2. Подробнее [о ключах элементов в коллекции.](05-tags.md#%D0%BA%D0%BB%D1%8E%D1%87-%D1%8D%D0%BB%D0%B5%D0%BC%D0%B5%D0%BD%D1%82%D0%B0-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)

> [!IMPORTANT]
> Метод `$priorityDefaultMethod` должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В качестве аргументов метод принимает два необязательных параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

> [!IMPORTANT]
> Метод указанный в `\Kaspi\DiContainer\Attributes\TaggedAs::$keyDefaultMethod` должен быть объявлен как `public static function`
> и возвращать тип `string`.
> В качестве аргументов метод принимает два необязательных параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

Пример получение «ленивой» коллекции из сервисов отмеченных тегом `tags.services.group_two`:
```php
// src/Classes/AnyClass.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class AnyClass {

    public function __construct(
        // будет получено как коллекция
        // с ленивой инициализацией результатов
        #[TaggedAs(name: 'tags.services.group_two')]
        private iterable $services
    ) {}

}
```
Пример получение «ленивой» коллекции из классов реализующих интерфейс `App\Inerfaces\SomeInterface::class`:
```php
// src/Classes/SomeService.php
namespace App\Classes;

use App\Inerfaces\SomeInterface;
use Kaspi\DiContainer\Attributes\TaggedAs;

class SomeService {

    public function __construct(
        #[TaggedAs(
            name: SomeInterface::class,
            priorityDefaultMethod: 'getPriorityForSomeInterface'
        )]
        private iterable $services
    ) {}

}
```
Атрибут можно применять так же **параметрам переменной длины**:
```php
// src/Classes/AnyService.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class AnyService {

    public function __construct(
        #[TaggedAs('tags.services.group_first', isLazy: false)]
        #[TaggedAs('tags.services.group_second', isLazy: false)]
        array ...$group
    ) {}

}
```
> [!WARNING]
> Для получения результат в параметр с типом `array` необходимо указать `\Kaspi\DiContainer\Attributes\TaggedAs::$isLazy = false`.

> [!WARNING]
> Параметр переменной длины является опциональным и если у него не задан
> PHP атрибут указывающий какой аргумент использовать
> для разрешения зависимости, то он будет пропущен.

> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

## Parameter
Атрибут может применяться к параметру функции, метода
для указания как разрешить зависимость через «параметры контейнера».

Сигнатура php атрибута:
```php
#[Parameter(string $name = '')]
```
Параметры:
- `$name` – имя параметра контейнера.

> [!NOTE]
> Атрибут может быть применен несколько раз к параметрам переменной длины (_variadic parameter_).

```php
namespace App\Services;

use App\Services\Qux;
use Kaspi\DiContainer\Attributes\Parameter;

final class Foo {
    public function __construct(
        private Qux $qux,
        #[Parameter('adminEmail')]
        private string $adminEmail,
    ) {}
}
```

> [!NOTE]
> Подробное [описание работы с параметрами контейнера](09-container-parameters.md).

## ParameterRuntime
Параметр контейнера времени исполнения. [Аналогичен PHP атрибуту `Parameter`](#parameter), но значение необходимо установить в контейнер
после его формирования.

Сигнатура php атрибута:
```php
#[ParameterRuntime(string $name = '', ?string $message = null)]
```
Параметры:
- `$name` – имя параметра контейнера.
- `$message` – дополнительное сообщение, если параметр контейнера еще не определен.

> [!NOTE]
> Атрибут может быть применен несколько раз к параметрам переменной длины (_variadic parameter_).

```php
namespace App\Services;

use App\Services\Qux;
use Kaspi\DiContainer\Attributes\ParameterRuntime;

final class Bar {
    public function __construct(
        private Qux $qux,
        #[ParameterRuntime('foo.parameter')]
        private string $value,
    ) {}
}
```

> [!NOTE]
> Подробное [описание работы с параметрами контейнера](09-container-parameters.md#параметры-контейнера-определяемые-во-время-выполнения).

