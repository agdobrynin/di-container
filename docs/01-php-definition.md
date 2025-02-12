# 🐘 DiContainer с конфигурированием в стиле php определений

Получение существующего класса и разрешение параметров в конструкторе:

```php
// Определения для DiContainer как array
use Kaspi\DiContainer\{DiContainer, DiContainerConfig};
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use function Kaspi\DiContainer\diAutowire;

$definitions = [
    // хэлпер функция для объявления тип зависимости
    diAutowire(
        // получить класс \PDO 
        definition: \PDO::class,
        // всегда возвращать тот же объект
        isSingleton: true
        )
            // установить параметр $dsn в конструкторе 'sqlite:/tmp/my.db'.
            ->bindArguments(
                dsn: 'sqlite:/tmp/my.db'
            )
            // Вызвать метод "setAttribute" и предать параметры в него
            ->setup('setAttribute', \PDO::ATTR_CASE, \PDO::CASE_UPPER),
];

$config = new DiContainerConfig();
$container = new DiContainer(definitions: $definitions, config: $config);
// либо можно использовать фабрику с конфигураций по умолчанию
// $container = (new DiContainerFactory())->make($definitions)
```
```php
// Объявление класса
namespace App;

class MyClass {
    public function __construct(public \PDO $pdo) {}
}
```
```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\MyClass::class); // $pdo->dsn === 'sqlite:/tmp/my.db' 
$myClass->pdo->query('...');

// получать один и тот же объект PDO::class
// так как в определении указан isSingleton=true
$myClassTwo = $container->get(App\MyClass::class);

var_dump(
    \spl_object_id($myClass->pdo) === \spl_object_id($myClassTwo->pdo)
); // true
```
> 🧙‍♂️ Для пример выше фактически будет выполнен следующий php код:
> ```php
> $pdo = new \PDO(dns: 'sqlite:/tmp/my.db');
> $pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_UPPER);
> $service = new App\MyClass($pdo);
> $service->pdo->query('...') // готовый сервис для использования
> ```

🚩 Реализация кода в [примере](https://github.com/agdobrynin/di-container/blob/main/examples/01-01-pdo.php)

## Объявления для определений контейнера:

### Определения для простых типов

Можно добавлять любые простые определения в виде массивов, строк или любых простых php типов.

```php
$definitions =  [
    'logger.name' => 'payment',
    'logger.file' => '/var/log/payment.log',
    'feedback.show-recipient' => false,
    'feedback.email' => [
        'help@my-company.inc',
        'boss@my-company.inc',
    ],
];

$container = (new DiContainerFactory())->make($definitions);

$container->get('logger.name'); // 'payment'
$container->get('logger.file'); // '/var/log/payment.log'
$container->get('feedback.show-recipient'); // FALSE
$container->get('feedback.email'); // array('help@my-company.inc', 'boss@my-company.inc')
```
> _Так же для некоторых случаев может понадобиться определение без обработки «как есть»,
> то нужно использовать хэлпер функцию [diValue](#divalue)._ 

### Объявления через хэлпер функции:

> 📑 Хэлпер функции имеют отложенную инициализацию параметров поэтому минимально влияют на начальную загрузку контейнера.

#### diAutowire

Автоматическое создание объекта и внедрения зависимостей.

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionConfigAutowireInterface;
use function \Kaspi\DiContainer\diAutowire;

diAutowire(string $definition, ?bool $isSingleton = null): DiDefinitionConfigAutowireInterface
```
Аргументы:
- `$definition` - имя класса с пространством имен представленный строкой. Можно использовать безопасное объявление через магическую константу `::class` - `MyClass::class`
- `$isSingleton` - используя паттерн singleton создавать каждый раз заново или единожды создав возвращать тот же объект.

> 🔌 Функция `diAutowire` возвращает объект реализующий интерфейс `DiDefinitionSetupInterface`.
> 
> Интерфейс представляет методы:
>   - `bindArguments` - аргументы для конструктора класса
>   - `setup` - вызов метода класса с параметрами (_setter method_)
>   - `bindTag` - добавляет тег с мета-данными для определения
 
**Аргументы для конструктора:**
```php
bindArguments(mixed ...$argument)`
```
> ❗ метод перезаписывает ранее определенные аргументы.
 
Можно использовать именованные аргументы параметров:
```php 
diAutowire(...)->bindArguments(var1: 'value 1', var2: 'value 2')
// public function __construct(string $var1, string $var2) {}
```
> 📝 для `bindArguments` будет работать авто-подстановка для параметров (_autowire_)

**Дополнительная настройка сервиса через методы класса (setters):**
```php 
setup(string $method, mixed ...$argument)
``` 
> 📝 для `setup` также будет работать авто-подстановка для параметров (_autowire_).

Можно указывать именованные аргументы:
```php
diAutowire(...)->setup('classMethod', var1: 'value 1', var2: 'value 2')
// $object->classMethod(string $var1, string $var2)
```
если в методе нет параметров, то аргументы указывать не нужно
```php
   diAutowire(...)
       ->bindArguments(...)
       ->setup('classMethodWithoutParams')
   // $object->classMethodWithoutParams()
```
При указании нескольких вызовов метода он будет вызван указанное количество раз и возможно с разными аргументами:
```php
diAutowire(...)
  ->setup('classMethod', var1: 'value 1', var2: 'value 2')
  ->setup('classMethod', var1: 'value 3', var2: 'value 4')
  // $object->classMethod('value 1', 'value 2');
  // $object->classMethod('value 3', 'value 4');
```
 
> ✔ [пример использования метода `diAutowire(...)->setup`](#пример-4)

**Указать теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```

```php
  diAutowire(...)
      ->bindTag('tags.rules', priority: 100)
```
Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

##### Идентификатор контейнера для diAutowire.
При конфигурировании идентификатор контейнера может быть сформирован на основе FQCN класса (**Fully Qualified Class Name**)

```php
use function Kaspi\DiContainer\diAutowire;

$definitions = [
    // идентификатор контейнера сформируется
    // из имени класса включая пространство имен
    diAutowire(\PDO::class)
        ->bindArguments(
            dsn: 'sqlite:/tmp/my.db'
        ),
    )
];
// эквивалентно
$definitions = [
    \PDO::class => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: 'sqlite:/tmp/my.db'
        ),
];
```
Если необходим другой идентификатор контейнера, то можно указывать так:
```php
use function Kaspi\DiContainer\diAutowire;

$definitions = [
    // $container->get('pdo-in-tmp-file')
    'pdo-in-tmp-file' => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: 'sqlite:/tmp/my.db'
        ),

    // $container->get('pdo-in-memory')
    'pdo-in-memory' => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: 'sqlite::memory:'
        ),
];
```
#### diCallable
Получение результата обработки `callable` типа.
```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use function \Kaspi\DiContainer\diCallable; 

diCallable(array|callable|string $definition, ?bool $isSingleton = null): DiDefinitionArgumentsInterface
```
Аргументы:
- `$definition` - значение которое `DiContainer` может преобразовать в [callable тип](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md#поддерживаемые-типы)
- `$isSingleton` - используя паттерн singleton создавать каждый раз заново или единожды создав возвращать тот же объект

> 🔌 Функция `diCallable` возвращает объект реализующий интерфейс `DiDefinitionArgumentsInterface`
> предоставляющий методы:
> - `bindArguments` - указать аргументы для определения
> - `bindTag` - добавляет тег с мета-данными для определения.

**Аргументы для определения:**
```php
bindArguments(mixed ...$argument)`
```
Можно указывать имена параметров используя именованные аргументы
 ```php
 bindArguments(var1: 'value 1', var2: 'value 2');
 // function(string $var1, string $var2) 
 ```
> ❗ метод `bindArguments` перезаписывает ранее определенные аргументы.

**Указать теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```
Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

##### Идентификатор контейнера.
🚩 При объявлении зависимости необходимо указать в конфигурации идентификатор контейнера.

Пример:
```php
// объявление класса
namespace App\Services;

class ServiceOne {
    public function __construct(private string $apiKey, private bool $debug) {}

    public static function makeForTest(string $apiKey): self {
        return new self($apiKey, true)
    }
    // some methods here
}
```
```php
use \Kaspi\DiContainer\DiContainerFactory;
use function \Kaspi\DiContainer\diCallable;

$definitions = [
    'services.one' => diCallable(
        definition: static fn () => new App\Services\ServiceOne(apiKey: 'my-api-key', false),
        isSingleton: true,
    ),

    'services.two' => diCallable(
        definition: [App\Services\ServiceOne::class, 'makeForTest'],
        isSingleton: false, 
    )
        ->bindArguments('my-other-api-key'),
];

$container = (new DiContainerFactory())->make($definitions);

// ...

var_dump($container->get('services.one') instanceof App\Services\ServiceOne); // true
var_dump($container->get('services.two') instanceof App\Services\ServiceOne); // true
```

🚩 Поддерживаемые [типы](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md#поддерживаемые-типы)
подробнее в разделе описывающий `DiContainer::call` 

> 📝 Так же доступно объявление через callback функцию которое будет корректно:
> ```php
> // для примера выше
> $definitions = [
>   'services.one' => static fn () => new App\Services\ServiceOne(apiKey: 'my-api-key', debug: false),
> ];
> ```
#### diGet
Определение как ссылки на другой идентификатор контейнера.

```php
use function \Kaspi\DiContainer\diGet;
 
diGet(string $containerIdentifier)
```
> У хэлпер функции нет дополнительных методов.

Пример:
```php
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;

$definitions = [
    'services.env-dsn' => diCallable(
        definition: static function () {
            return match (getenv('APP_ENV')) {
                'prod' => 'sqlite:/databases/my-app/app.db',
                'test' => 'sqlite::memory:',
                default => 'sqlite:/tmp/mydb.db',  
            };
        },
        isSingleton: true
    ),

    // ...

    diAutowire(\PDO::class)
        ->bindArguments(dsn: diGet('services.env-dsn')), // ссылка на определение
];
```
#### diValue

Определение без обработки — «как есть».

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use function \Kaspi\DiContainer\diValue;
 
diValue(mixed $value): DiDefinitionTagArgumentInterface
```

> 🔌 Функция `diValue` возвращает объект реализующий интерфейс `DiDefinitionTagArgumentInterface`
> предоставляющий метод:
> - `bindTag` - добавляет тэг с мета-данными для определения.

**Указать теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```
Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

##### Идентификатор контейнера.
🚩 При объявлении зависимости необходимо указать в конфигурации идентификатор контейнера.

---

**Пример когда надо объявить аргумент «как есть»**:
```php
// класс
class ParameterIterableVariadic
{
    private array $parameters;

    public function __construct(iterable ...$parameter)
    {
        $this->parameters = $parameter;
    }
    //... some logic
}
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diValue;

$definition = [
    diAutowire(ParameterIterableVariadic::class)
        ->bindArguments(
            parameter: diValue(['ok'])
        )
];

$container = (new DiContainerFactory())->make($definition);
```
Пример использования тегов для хэлпер функции `diValue`:
```php
namespace App\Notifications;

class CompanyStaff {
    public function __construct(private array $emails) {}
    //...
}
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs, diValue};

$definitions = [
    'admin.email.tasks' => diValue('runner@company.inc')
        ->bindTag('tags.system-emails'),

    'admin.email.report' => diValue('vasiliy@company.inc')
        ->bindTag('tags.system-emails'),

    'admin.email.stock' => diValue('stock@company.inc')
        ->bindTag('tags.system-emails'),

    diAutowire(App\Notifications\CompanyStaff::class)
        ->bindArguments(
            emails: diTaggedAs(
                tag: 'tags.system-emails',
                isLazy: false,
                useKeys: false // 🚩 не использовать строковые ключи коллекции
            )
        ),
];

$container = (new DiContainerFactory())->make($definition);

$notifyStaff = $container->get(App\Notifications\CompanyStaff::class);
// $notifyStaff->emails массив ['runner@company.inc', 'vasiliy@company.inc', 'stock@company.inc']
```

> 🚩 Подробнее [о ключах элементов в коллекции.](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md#%D0%BA%D0%BB%D1%8E%D1%87-%D1%8D%D0%BB%D0%B5%D0%BC%D0%B5%D0%BD%D1%82%D0%B0-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)

#### diProxyClosure

Определение для отложенной инициализации сервиса через Closure тип.

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use function Kaspi\DiContainer\diProxyClosure;

diProxyClosure(string $definition, ?bool $isSingleton = null): DiDefinitionTagArgumentInterface
```
Аргументы:

- `$definition` - имя определения или идентификатора контейнера которое содержит сервис.
- `$isSingleton` - используя паттерн singleton создавать каждый раз заново или единожды создав возвращать тот же объект

> 🔌 Функция `diProxyClosure` возвращает объект реализующий интерфейс `DiDefinitionTagArgumentInterface`
> предоставляющий метод:
> - `bindTag` - добавляет тэг с мета-данными для определения.

**Указать теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```
Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

##### Идентификатор контейнера.
🚩 При объявлении зависимости необходимо указать в конфигурации идентификатор контейнера.

---

Реализация ленивой инициализации зависимости через функцию обратного вызова.

Такое объявление сервиса пригодится для «тяжёлых» зависимостей,
требующих длительного времени инициализации или ресурсоёмких вычислений.

Пример для отложенной инициализации сервиса:
```php
// Класс с «тяжёлыми» зависимостями, много ресурсов на инициализацию.
class HeavyDependency {
    public function __construct(...) {}
    public function doMake() {}
}

class ClassWithHeavyDependency {
    /**
     * @param Closure(): HeavyDependency $heavyDependency
     */
    public function __construct(
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
```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\diProxyClosure;

$definition = [
    diAutowire(ClassWithHeavyDependency::class)
        ->bindArguments(
            heavyDependency: diProxyClosure(HeavyDependency::class),
        )
];

$container = (new DiContainerFactory())->make($definition);

// ...

// свойство ClassWithHeavyDependency::$heavyDependency
// ещё не инициализировано.
$classWithHeavyDep = $container->get(ClassWithHeavyDependency::class);

// Внутри метода инициализируется
// свойство ClassWithHeavyDependency::$heavyDependency
// через Closure вызов (callback функция) 
$classWithHeavyDep->doHeavyDependency();
```
При таком объявлении сервис `$heavyDependency` будет инициализирован
только после обращения к свойству `ClassWithHeavyDependency::$heavyDependency`
а не в момент разрешения зависимости `ClassWithHeavyDependency::class`.

> 📝 Для подсказок IDE autocomplete можно использовать PhpDocBlock:
> ```php
>  /**
>   * 🚩 Подсказка для IDE при авто-дополении (autocomplete).
>   * @param Closure(): HeavyDependency $heavyDependency
>   */
>   public function __construct(
>       private \Closure $heavyDependency,
>       private LiteDependency $liteDependency,
>   ) {}
> ```
#### diTaggedAs
Определение для получения коллекции сервисов отмеченных тегом.
Результат выполнения может быть применен для параметров с типом `iterable` и `array`.
```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use function Kaspi\DiContainer\diTaggedAs;

diTaggedAs(string $tag, bool $isLazy = true, ?string $priorityDefaultMethod = null, bool $useKeys = true): DiDefinitionNoArgumentsInterface
```
Аргументы:
- `$tag` - имя тега на сервисах которые нужно собрать из контейнера.
- `$isLazy` - получать сервисы только во время обращения или сразу всё.
- `$priorityDefaultMethod` - если получаемый сервис является php классом
и у него не определен `priority` или `priorityMethod`, то будет выполнена попытка
получить значение `priority` через вызов указанного метода.
- `$useKeys` - использовать именованные строковые ключи в коллекции.
По умолчанию в качестве ключа элемента в коллекции используется идентификатор
определения в контейнере (_container identifier_).
Если значение `$useKeys = false` то ключ элемента в коллекции будет представлен целым числом.
Подробнее [о ключах элементов в коллекции.](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md#%D0%BA%D0%BB%D1%8E%D1%87-%D1%8D%D0%BB%D0%B5%D0%BC%D0%B5%D0%BD%D1%82%D0%B0-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)

> Метод `$priorityDefaultMethod` должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В качестве аргументов метод принимает два параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;
>
>  Подробнее [о приоритизации в коллекцции](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md#%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)

> У хэлпер функции нет дополнительных методов.

**Пример использования хэлпер функции diTaggedAs для аргумента:**
```php
namespace App\Srv;

final class MyClass {
    public function __construct(private iterable $rules) {}
    // ...    
}
```
```php
use Kaspi\DiContainer\DiContainerFactor;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$container = (new DiContainerFactory())->make([
    diAutowire(App\Srv\MyClass::class)
        ->bindArguments(
            rules: diTaggedAs('tags.lite-rules')
        ),

    diAutowire(App\Rules\RuleA::class)
        ->bindTag('tags.lite-rules'),

    diAutowire(App\Rules\RuleB::class),

    diAutowire(App\Rules\RuleC::class)
        ->bindTag('tags.lite-rules', priority: 100),
]);

$myClass = $container->get(App\Srv\MyClass::class);
// $myClass->rules содержит итерируемую коллекцию классов
// отсортированные по 'priority' - RuleC, RuleA
```
> Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

## Внедрение значений зависимостей по ссылке на другой идентификатор контейнера.

Для внедрения зависимостей по ссылке используется
хэлпер функция [diGet](#diget).

```php
// Объявление класса
namespace App;

class MyUsers {
    public function __construct(public array $users, string $type) {}
}

class MyEmployers {
    public function __construct(public array $employers, string $type) {}
}
```

```php
// Определения для DiContainer
use App\{MyUsers, MyEmployers};
use Kaspi\DiContainer\DiContainerFactory;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

$definitions = [
    'data' => ['user1', 'user2'],
    
    // ... more definitions
    
    // внедрение зависимости аргумента по ссылке на контейнер-id
    diAutowire(App\MyUsers::class)
        ->bindArguments(
            users: diGet('data'), type: 'Some value'
        ),

    diAutowire(App\MyEmployers::class)
        ->bindArguments(
            employers: diGet('data'), type: 'Other value'
        ),
];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\{MyUsers, MyEmployers};

/** @var MyUsers::class $users */
$users = $container->get(MyUsers::class);
print implode(',', $users->users); // user1, user2

/** @var MyEmployers::class $employers */
$employers = $container->get(MyEmployers::class);
print implode(',', $employers->employers); // user1, user2
```
## Внедрение значений зависимостей по имени аргументов

Если контейнер не смог определить зависимость по типу аргумента, то будет
выполнена попытка получить значение по имени аргумента.
```php
// определение класса
namespace App;

class ServiceLocation {

    public function __construct(public string $locationCity) {}
}
```
```php
// определения для контейнера
use Kaspi\DiContainer\DiContainerFactory;

$definitions = [
    'locationCity' => 'Vice city',
];

$container = (new DiContainerFactory())->make($definitions);

$container->get(App\ServiceLocation::class)->locationCity; // Vice city
```
## Получение класса по интерфейсу

### Получение через функцию обратного вызова – `\Closure`:

```php
// Объявление класса
use Psr\Log\LoggerInterface;

namespace App;

class MyLogger {

    public function __construct(protected LoggerInterface $logger) {}
    
    public function logger(): LoggerInterface {
        return $this->logger;
    }
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\{Logger, Handler\StreamHandler, Level};
use function Kaspi\DiContainer\diCallable;

$simpleDefinitions = [
    'logger_file' => '/path/to/your.log',
    'logger_name' => 'app-logger',
];

// ... many definitions ...

$interfaceDefinition = [
    LoggerInterface::class => diCallable(
        definition: static function (ContainerInterface $c) {
            return (new Logger($c->get('logger_name')))
                ->pushHandler(new StreamHandler($c->get('logger_file')));    
        },
        isSingleton: true
    )
];

$container = (new DiContainerFactory())->make(
    \array_merge($simpleDefinitions, $interfaceDefinition)
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\MyLogger::class);
$myClass->logger()->debug('...');
```

### Получение по интерфейсу через объявления в контейнере:

```php
// Объявление классов
namespace App;

interface ClassInterface {}

class ClassFirst implements ClassInterface {

    public function __construct(public string $file) {}
}
```

```php
// Определения для DiContainer
use App\{ClassFirst, ClassInterface};
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\diAutowire;

$definition = [
    ClassInterface::class => diAutowire(ClassFirst::class)
        // без указания именованного аргумента,
        // подставит для конструктора в параметр с индексом 0.
        ->bindArguments('/var/log/app.log')
];

$container = (new DiContainerFactory())->make($definition);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\ClassInterface::class);
print $myClass->file; // /var/log/app.log
```
#### Отдельное определение для класса и привязка интерфейса к реализации для примера выше:

```php
// Определения для DiContainer - отдельно класс и реализации.
use App\{ClassFirst, ClassInterface};
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diGet};

$classesDefinitions = [
    diAutowire(ClassFirst::class)
        ->bindArguments(file: '/var/log/app.log')
];

// ... many definitions ...

$interfacesDefinitions = [
    ClassInterface::class => diGet(ClassFirst::class),
];

$container = (new DiContainerFactory())->make(
    \array_merge($classesDefinitions, $interfacesDefinitions)
);
```

## 🧙‍♂️ Разрешение зависимости в контейнере с помощью фабрики.

> ⚠ Класс фабрика должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`.

```php
// Объявления классов
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

namespace App;

class  MyClass {

    public function __construct(private Db $db) {}
    // ...
}

// ....

class FactoryMyClass implements DiFactoryInterface {

    public function __invoke(ContainerInterface $container): MyClass {
        return new MyClass(new Db(...));
    }    
}
```

```php
// определения для контейнера
use Kaspi\DiContainer\DiContainerFactory;
use function \Kaspi\DiContainer\diAutowire;

$definitions = [
    App\MyClass::class => diAutowire(App\FactoryMyClass::class)
];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$container->get(App\MyClass::class); // instance of App\MyClass
```

> 📝 Для класса реализующего интерфейс `DiFactoryInterface` так же могут быть
> разрешены зависимости в конструкторе автоматически или на основе конфигурации.

## Callable тип как определение (definition).

Определения могут быть объявлены `callable` типом (см. [Callable](https://www.php.net/manual/ru/language.types.callable.php)), например такие —
функция, функция обратного вызова (callback), статический метод класса.

```php
// определение класса
namespace App;

class ServiceLocation {

    public function __construct(public string $city) {}
}

// ...

class ClassWithStaticMethods
{

    public static function doSomething(
        ServiceLocation $serviceLocation // Внедрение зависимости по типу
    ): \stdClass {
        return (object) [
            'name' => 'John Doe',
            'age' => 32,
            'gender' => 'male',
            'city' => $serviceLocation->city,
        ];
    }
}
```
```php
use Kaspi\DiContainer\Interfaces\{DiContainerFactory};
use function Kaspi\DiContainer\{diAutowire, diCallable};

$defServices = [
    diAutowire(App\ServiceLocation::class)
        ->bindArguments(city: 'Vice city'),
];

// ... many definitions ...

$defCustom = [
    // Статический метод класса является callable типом.
    // При вызове метода автоматически внедрится зависимость по типу ServiceLocation. 
    'doSomething' => diCallable('App\ClassWithStaticMethods::doSomething'),
];

$container = (new DiContainerFactory())->make(
    \array_merge($defServices, $defCustom)
);

// ...

// получение данных
$container->get('doSomething'); // (object) ['name' => 'John Doe', 'age' => 32, 'gender' => 'male', 'city' => 'Vice city']
```

> 📝 Если у `callable` определения присутствуют аргументы, то они могут быть разрешены контейнером
> автоматически включая использование атрибута _#[Inject]_.

## Разрешение аргументов переменной длины

Если необходимо передать несколько аргументов для `variadic` параметра используя имя параметра
то необходимо объявлять аргументы как массив `[]`.

```php
// Объявления классов
namespace App\Rules;

interface RuleInterface {}
class RuleA implements RuleInterface {}
class RuleB implements RuleInterface {}
class RuleC implements RuleInterface {}

class RuleGenerator {

    private iterable $rules;

    public function __construct(RuleInterface ...$inputRule) {
        $this->rules = $inputRule;
    }
    
    public function getRules(): array {
        return $this->rules;
    }
}
```
```php
// определения для контейнера
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diGet};

$definition = [
    'ruleC' => diAutowire(App\Rules\RuleC::class),

    diAutowire(App\Rules\RuleGenerator::class)
        ->bindArguments(
            // имя параметра $inputRule в конструкторе
            inputRule:
                [ // <-- обернуть параметры в массив для variadic типов если их несколько.
                    diAutowire(App\Rules\RuleB::class),
                    diAutowire(App\Rules\RuleA::class),
                    diGet('ruleC'), // <-- получение по ссылке
                ], // <-- обернуть параметры в массив если их несколько.            
        )
];

$container = (new DiContainerFactory())->make($definition);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
assert($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
assert($ruleGenerator->getRules()[2] instanceof App\Rules\RuleС); // true
```
Если необходимо передать только один аргумент и использовать имя параметра, то объявление будет таким:
```php
// для примера выше
$definition = [
    diAutowire(App\Rules\RuleGenerator::class)
        ->bindArguments(
            // имя параметра $inputRule в конструкторе
            inputRule: diAutowire(App\Rules\RuleB::class),            
        )
];
```

⛏ Если не использовать имя параметра то передавать аргументы по индексу в конструкторе можно просто перечисляя нужные определения:
```php
 // Передать три аргумента в конструктор класс
 diAutowire(App\Rules\RuleGenerator::class)
   // Передать в параметр с индексом 0 значение.
   ->bindArguments(
       diAutowire(App\Rules\RuleB::class),

       diAutowire(App\Rules\RuleA::class),

       diGet('ruleC'), // <-- получение по ссылке
   );
```

## Примеры использования для конфигурирования:

### Пример #1 

Один класс как самостояние определение со своими аргументами, и как реализация интерфейса, но со своими аргументами

```php
// объявления классов
namespace App;

interface SumInterface {
    public function getInit(): int;
}

class Sum implements SumInterface {

    public function __construct(private int $init) {}

    public function getInit(): int {
        return $this->init;
    }
}
```
```php
// Определения контейнера
use Kaspi\DiContainer\diDefinition;

use function Kaspi\DiContainer\diAutowire;

$definition = [
    App\SumInterface::class => diAutowire(App\Sum::class)
        ->bindArguments(init: 50),

    diAutowire(App\Sum::class)
        ->bindArguments(init: 10),
];

$c = (new DiContainerFactory())->make($definition);
// … вызова определения
print $c->get(App\SumInterface::class)->getInit(); // 50
print $c->get(App\Sum::class)->getInit(); // 10
```

### Пример #2
Создание объекта без сохранения результата в контейнере.
```php
class MyApiRequest {

    public function __construct(
         private SomeDependency $dependency,
         private string $endpoint
    ) {....}

    public function request(): string
    { 
       // .... 
    }
}
```
```php
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;

/** @var MyApiRequest $apiV1 */
$apiV1 = (new DiDefinitionAutowire(MyApiRequest::class))
    // SomeDependency $dependency будет разрешено контейнером
   ->bindArguments(endpoint: 'http://www.site.com/apiv1/')
  ->setContainer($container)
  ->invoke();
  
$apiV1->request(); // выполнить запрос

/** @var MyApiRequest $apiV2 */
$apiV2 = (new DiDefinitionAutowire(MyApiRequest::class))
    // SomeDependency $dependency будет разрешено контейнером
   ->bindArguments(endpoint: 'http://www.site.com/apiv2/')
  ->setContainer($container)
  ->setUseAttribute(true) // ✔ использовать php-атрибуты
  ->invoke();

$apiV2->request(); // выполнить запрос
```
- Такой вызов работает как `DiContainer::get`, но будет каждый раз выполнять разрешение зависимостей и создание **нового объекта**;
- Подстановка аргументов для создания объекта так же может быть каждый раз разной;

### Пример #3
Заполнение коллекции на основе callback функции.
> 🚩 Похожий функционал можно реализовать [через тегированные определения](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

```php
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}

class RuleC implements RuleInterface {}
```
```php
namespace App\Services;

use App\Rules\RuleInterface;

class IterableArg
{
    /**
     * @param RuleInterface[] $rules
     */
    public function __construct(private iterable $rules) {}
}
```
```php
use App\Rules\{RuleA, RuleB, RuleC}; 
use App\Services\IterableArg;
use Kaspi\DiContainer\DiContainerFactory;

$definitions = [
    'services.rule-list' => static fn (RuleA $a, RuleB $b, RuleC $c) => \func_get_args(),
    
    // ... many definitions ...
    
    diAutowire(IterableArg::class)
        ->bindArguments(
            // в параметр $rules передать сервис по ссылке
            rules: diGet('services.rule-list')
        ),
];


$container = (new DiContainerFactory())->make($definitions);

$class = $container->get(IterableArg::class);
```
> 📝 Если требуется чтобы сервис `services.rule-list` был объявлен как `isSingleton`
> необходимо использовать хэлпер функцию `diCallable`
> ```php
> $definitions = [
>   'services.rule-list' => diCallable(
>       definition: static fn (RuleA $a, RuleB $b, RuleC $c) => \func_get_args(),
>       isSingleton: true
>   ),
> ];
> ```

### Пример #4
Использование дополнительной настройки сервиса через сеттер-методы (_setter methods_):
```php
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}

class RuleC implements RuleInterface {}
```
```php
namespace App\Services;

use App\Rules\RuleInterface;

class OtherClass {}

class Rules
{
    /**
     * @param RuleInterface[] $rules
     */
    private $rules;

    public function addRule(OtherClass $other, RuleInterface $rule): static {
        $this->rules[] = $rule;
        
        return $this;
    }
    
    /**
     * @return RuleInterface[]
     */
    public function getRules(): array {
        return $this->rules;
    }
}
```
```php
use App\Rules\{RuleA, RuleB, RuleC};
use App\Services\{Rules, OtherClass};
use Kaspi\DiContainer\{diAutowire, diGet, DiContainerFactory};

$definitions = [
    'services.other' => diAutowire(OtherClass::class),
    diAutowire(Rules::class)
        // использую именованный аргумент для передачи в метод
        // параметр $other в методе будет заполнен через механизм autowire
        ->setup('addRule', rule: diGet(RuleA::class))
        ->setup('addRule', rule: diGet(RuleB::class))
        // передаю по индексу все аргументы в метод
        ->setup('addRule', diGet('services.other'), diGet(RuleC::class))
];

$container = (new DiContainerFactory())->make($definitions);

$class = $container->get(Rules::class);
$class->getRules(); // массив содержащий классы RuleA, RuleB, RuleC
```
