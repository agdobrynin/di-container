# 📦 DiContainer с конфигурированием на основе php-определений

Получение существующего класса и разрешение встроенных типов параметров в конструкторе:

```php
// Определения для DiContainer как array
use Kaspi\DiContainer\{DiContainer, DiContainerConfig};
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use function Kaspi\DiContainer\diAutowire;

$definitions = [
    diAutowire(\PDO::class,true)
        ->addArgument('dsn', 'sqlite:/tmp/my.db'),
];

$config = new DiContainerConfig();
$container = new DiContainer(definitions: $definitions, config: $config);
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
```
### Объявления для определений контейнера:

#### Определения для простых типов

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

#### Объявления через функции-хэлперы:

> 📑 Функции-хэлперы имеют отложенную инициализацию параметров поэтому минимально влияют на начальную загрузку контейнера.

##### diAutowire - автоматическое создание объекта и внедрения зависимостей.

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use function \Kaspi\DiContainer\diAutowire;

diAutowire(string $definition, ?bool $isSingleton = null): DiDefinitionAutowireInterface
```
> 🔌 Функция `diAutowire` возвращает объект реализующий интерфейс `DiDefinitionAutowireInterface`.
> Можно указать аргументы для "определения" через методы:
> - `addArgument(string $name, mixed $value)`
> - `addArguments(array $arguments)`

При конфигурировании если не нужен идентификатор контейнера отличный от имени определения, то можно указать так:

```php
use function Kaspi\DiContainer\diAutowire;

$definitions = [
    // идентификатор контейнера сформируется из аргумента `$definition`
    diAutowire(\PDO::class)
        ->addArgument('dsn', 'sqlite:/tmp/my.db'),
    )
];
// эквивалентно
$definitions = [
    \PDO::class => diAutowire(\PDO::class)
        ->addArgument('dsn', 'sqlite:/tmp/my.db'),
];
```
Если необходим другой идентификатор, то можно указывать так:
```php
$definitions = [
    // $container->get('pdo-in-tmp')
    'pdo-in-tmp' => diAutowire(\PDO::class)
        ->addArgument('dsn', 'sqlite:/tmp/my.db'),
    // $container->get('pdo-in-local')
    'pdo-in-local' => diAutowire(\PDO::class)
        ->addArgument('dsn', 'sqlite:/var/local/my.db'),
];
```
##### diCallable - получение результата обработки `callable` типа.


```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use function \Kaspi\DiContainer\diCallable; 

diCallable(array|callable|string $definition, ?bool $isSingleton = null): DiDefinitionAutowireInterface
```

> 🔌 Функция `diCallable` возвращает объект реализующий интерфейс `DiDefinitionAutowireInterface`.
> Можно указать аргументы для "определения" через методы:
> - `addArgument(string $name, mixed $value)`
> - `addArguments(array $arguments)`

При объявлении зависимости необходимо указать в конфигурации идентификатор контейнера.
```php
namespace App\Services;

class ServiceOne {
    public function __construct(string $apiKey) {}
    // some methods here
}
```
```php
use \Kaspi\DiContainer\DiContainerFactory;
use function \Kaspi\DiContainer\diCallable;

$definitions = [
    'services.one' => diCallable(
        definition: static fn () => new App\Services\ServiceOne(apiKey: 'my-api-key'),
        isSingleton: true,
    )
];

$container = (new DiContainerFactory())->make($definitions);

// ...

var_dump($container->get('services.one') instanceof App\Services\ServiceOne); // true
```

> 🚩 Поддерживаемые [типы](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md#поддерживаемые-типы)
> подробнее в разделе описывающий `DiContainer::call` 

> 📝 Так же доступно объявление через callback функцию которое будет корректно:
> ```php
> // для примера выше
> $definitions = [
>   'services.one' => static fn () => new App\Services\ServiceOne(apiKey: 'my-api-key'),
> ];
> ```
##### diReference - объявление аргумента или определения как ссылки на другой идентификатор контейнера.

```php
use function \Kaspi\DiContainer\diReference;
 
diReference(string $containerIdentifier)
```
Пример:
```php
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diReference;

$definitions = [
    'services.env-dsn' => diCallable(definition: static function () {
        getenv('APP_ENV') !== 'prod'
            ? 'sqlite:/tmp/mydb.db'
            : 'sqlite:/databases/my-app/app.db'
    }, isSingleton: true),

    // ...

    diAutowire(\PDO::class)
        ->addArgument('dsn', diReference('services.env-dsn')), // ссылка на определение
    )
];
```

## Внедрение значений зависимостей аргументов по ссылке на другой идентификатор контейнера.

Для внедрения зависимостей в аргументы по ссылке используется
функция-хэлпер [diReference](#direference).

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
use function Kaspi\DiContainer\diReference;

$definitions = [
    'data' => ['user1', 'user2'],
    
    // ... more definitions
    
    // внедрение зависимости аргумента по ссылке на контейнер-id
    diAutowire(App\MyUsers::class)
        ->addArgument('users', diReference('data'))
        ->addArgument('type', 'Some value'),
    diAutowire(App\MyEmployers::class)
        // добавить много аргументов за один раз
        ->addArguments([
            'employers' => diReference('data'),
            'type' => 'Other value',
        ]),
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
        ->addArgument('file', '/var/log/app.log')
];

$container = (new DiContainerFactory()->make($definition);
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
use function Kaspi\DiContainer\{diAutowire, diReference};

$classesDefinitions = [
    diAutowire(ClassFirst::class)
        ->addArgument('file', '/var/log/app.log')
];

// ... many definitions ...

$interfacesDefinitions = [
    ClassInterface::class => diReference(ClassFirst::class),
];

$container = (new DiContainerFactory()->make(
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
        ->addArguments(['city' => 'Vice city']),
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

Каждое определение для `variadic` аргумента необходимо объявлять как массив `[]` если необходимо несколько зависимостей.

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
use function Kaspi\DiContainer\{diAutowire, diReference};

$definition = [
    'ruleC' => App\Rules\RuleC::class,
    diAutowire(App\Rules\RuleGenerator::class)
        ->addArgument(
            name: 'inputRule', // имя аргумента в конструкторе
            value: [ // <-- обернуть параметры в массив для variadic типов если их несколько.
                diAutowire(App\Rules\RuleB::class),
                diAutowire(App\Rules\RuleA::class),
                diReference('ruleC'), // <-- получение по ссылке
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

## Примеры использования для конфигурирования:

## Пример #1 

Один класс как самостояние определение со своими аргументами, и как реализация интерфейса, но со своими аргументами

```php
// объявления классов
namespace App;

interface SumInterface {}

class Sum implements SumInterface {
    public function __construct(public int $init) {}
}
```
```php
// Определения контейнера
use Kaspi\DiContainer\diDefinition;

use function Kaspi\DiContainer\diAutowire;

$definition = [
    App\SumInterface::class => diAutowire(App\Sum::class)
        ->addArgument('init', 50),
    diAutowire(App\Sum::class)
        ->addArgument('init', 10),
];

$c = (new DiContainerFactory())->make($definition);
// … вызова определения
print $c->get(App\SumInterface::class)->init; // 50
print $c->get(App\Sum::class)->init; // 10
```
