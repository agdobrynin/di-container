# 📦 DiContainer с конфигурированием на основе php-определений

Получение существующего класса и разрешение встроенных типов параметров в конструкторе:

```php
// Определения для DiContainer как array
use Kaspi\DiContainer\{DiContainer, DiContainerConfig};
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

use function Kaspi\DiContainer\diAutowire;

$definitions = [
    diAutowire(
        definition: \PDO::class,
        arguments: ['dsn' => 'sqlite:/opt/databases/mydb.sq3'], 
        isSingleton: true,
    )
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
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```
Функции-хэлперы определения имеют отложенную инициализацию параметров поэтому минимально влияют на начальную загрузку контейнера.

### Доступные функции-хэлперы для определений контейнера:

#### diAutowire

Автоматическое создание объекта и внедрения зависимостей.

```php
use function \Kaspi\DiContainer\diAutowire;

diAutowire(string $definition, array $arguments = [], ?bool $isSingleton = null)
```

При конфигурировании не нужно указывать идентификатор контейнера — он сформируется из аргумента `$definition`

```php
use  function Kaspi\DiContainer\diAutowire;

$definitions = [
    diAutowire(
        definition: \PDO::class,
        arguments: ['dsn' => 'sqlite:/opt/databases/mydb.sq3'], 
        isSingleton: true,
    )
];
// эквивалентно
$definitions = [
    \PDO::class => diAutowire(
        definition: \PDO::class,
        arguments: ['dsn' => 'sqlite:/opt/databases/mydb.sq3'], 
        isSingleton: true,
    )
];
```

#### diCallable

Получение результата обработки `callable` типа, внедрения зависимостей при необходимости в функцию `callable`.

```php
use function \Kaspi\DiContainer\diCallable; 

diCallable(array|callable|string $definition, array $arguments = [], ?bool $isSingleton = null)
```

При объявлении зависимости необходимо указать в конфигурации идентификатор контейнера.

```php
use function \Kaspi\DiContainer\diCallable;

$definitions = [
    'services.one' => diCallable(
        definition: static fn () => new App\Services\ServiceOne(apiKey: 'my-api-key'),
        isSingleton: true,
    )
];

$container = (new \Kaspi\DiContainer\DiContainerFactory())->make($definitions);

// ...

var_dump($container->get('services.one') instanceof App\Services\ServiceOne); // true
```

> 🚩 Поддерживаемые [типы](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md#поддерживаемые-типы)
> подробнее в разделе описывающий `DiContainer::call` 

#### diValue

Объявление простого значения не требующего разрешения зависимостей.

При объявлении зависимости необходимо указать в конфигурации идентификатор контейнера.

```php
use function \Kaspi\DiContainer\diValue;

diValue(mixed $definition)
```

```php
use function Kaspi\DiContainer\diValue;

$definitions = [
    'log' => diValue(
        definition: ['var' => 'value']
    )
];

$container = (new \Kaspi\DiContainer\DiContainerFactory())->make($definitions);

// ...

var_dump($container->get('log')); // array('var' => 'value')
```

> 📝 [Пример и объяснение применения](#определения-для-простых-типов)

#### diReference

Объявление аргумента или определения как ссылки на другой идентификатор контейнера.

```php
use function \Kaspi\DiContainer\diReference;
 
diReference(array|callable|string $definition, array $arguments = [], ?bool $isSingleton = null)
```

## Разрешение типов аргументов в конструкторе по имени:

```php
// Объявление класса
namespace App;

class MyUsers {
    public function __construct(public array $listOfUsers) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

// При разрешении аргументов конструктора можно в качестве идентификатора контейнера
// использовать имя аргумента
$container = (new DiContainerFactory())
    ->make([
        'listOfUsers' => [
            'John',
            'Arnold',
        ];
    ]);
```
```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyUsers;

/** @var MyUsers::class $users */
$users = $container->get(MyUsers::class);
print implode(',', $users->users); // John, Arnold
```

## Внедрение значений зависимостей аргументов по ссылке на другой идентификатор контейнера.

Для внедрения зависимостей в аргументы по ссылке используется
функция-хэлпер [diReference](#direference).

### Разрешение простых (builtin) типов аргументов в объявлении:

```php
// Объявление класса
namespace App;

class MyUsers {
    public function __construct(public array $users) {}
}

class MyEmployers {
    public function __construct(public array $employers) {}
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
    diAutowire(App\MyUsers::class, ['users' => diReference('data')]),
    diAutowire(App\MyEmployers::class, ['employers' => diReference('data')])
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

## Получение класса по интерфейсу

Получение через функцию обратного вызова (`\Closure`):

```php
// Объявление класса
namespace App;

use Psr\Log\LoggerInterface;

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

$simpleDefinitions = [
    'logger_file' => '/path/to/your.log',
    'logger_name' => 'app-logger',
];

// ... many definitions ...

$interfaceDefinition = [    
    LoggerInterface::class =>, static function (ContainerInterface $c) {
        return (new Logger($c->get('logger_name')))
            ->pushHandler(new StreamHandler($c->get('logger_file')));
    }
];

$container = (new DiContainerFactory())->make(
    \array_merge($simpleDefinitions, $interfaceDefinition)
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyLogger;

/** @var MyClass $myClass */
$myClass = $container->get(MyLogger::class);
$myClass->logger()->debug('...');
```

### Получение через объявления в контейнере:

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
use App\ClassFirst;
use App\ClassInterface;
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
use App\ClassInterface;

/** @var ClassFirst $myClass */
$myClass = $container->get(ClassInterface::class);
print $myClass->file; // /var/log/app.log
```
### Отдельное определение для класса и привязка интерфейса к реализации для примера выше:

```php
// Определения для DiContainer - отдельно класс и реализации.
use App\ClassFirst;
use App\ClassInterface;
use Kaspi\DiContainer\DiContainerFactory;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

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
namespace App;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

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
## Callable тип как определение (definition).

Определения могут быть объявлены `callable` типом (см. [Callable](https://www.php.net/manual/ru/language.types.callable.php)), например такие —
функция, функция обратного вызова, статический метод класса.

```php
// определение класса
namespace App;

class ServiceLocation {
    public function __construct(public string $city) {}
}

// ...

class ClassWithStaticMethods
{
    public static function doSomething(ServiceLocation $serviceLocation): \stdClass
    {
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

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;

$expect = (object) ['name' => 'John Doe', 'age' => 32, 'gender' => 'male', 'city' => 'Vice city'];

$defServices = [
    diAutowire(App\ServiceLocation::class, ['city' => 'Vice city'])
];

// ... many definitions ...

$defCustom = [
    // статический метод класса является callable типом.
    'doSomething' => diCallable('App\ClassWithStaticMethods::doSomething'),
];

$container = (new DiContainerFactory())->make(
    \array_merge($defServices, $defCustom)
);
// получение данных
$expect === $container->get('doSomething'); // true
```

> 📝 Если у `callable` определения присутствуют аргументы, то они могут быть разрешены контейнером
> автоматически включая использование атрибутов
> _#[InjectContext]_, _#[DiFactory]_.

## Разрешение аргументов переменной длины

Каждое определение для `variadic` аргумента необходимо объявлять как массив `[]`.

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

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

$definition = [
    'ruleC' => App\Rules\RuleC::class,
    diAutowire(App\Rules\RuleGenerator::class)
        ->addArgument(
            name: 'inputRule', // имя аргумента в конструкторе
            value: [ // <-- обернуть параметры в массив для variadic типов
                diAutowire(App\Rules\RuleB::class),
                diAutowire(App\Rules\RuleA::class),
                diReference('ruleC'), // <-- получение по ссылке
            ], // <-- обернуть параметры в массив            
        )
];

$container = (new DiContainerFactory())->make($definition);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
assert($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
assert($ruleGenerator->getRules()[2] instanceof App\Rules\RuleС); // true
```
## Определения для простых типов

💥 В некоторых случая необходимо конфигурирование контейнера используя `Kaspi\DiContainer\diValue` так как алгоритм
разрешения зависимостей пытается автоматически определить является ли классом определения или callable типом.

Пример когда значение `log` будет воспринято как `callable` тип (внутренняя функция php `\log(float $num)`:
```php
use Kaspi\DiContainer\DiContainerFactory;
$container = (new DiContainerFactory())->make([
    'log' => ['a' => 'aaa'],
]);
$container->get('log'); // 💥 ошибка при получении
// Kaspi\DiContainer\Exception\NotFoundException:
//      Unresolvable dependency. Parameter #0 [ <required> float $num ] in log.
```

В таком случае делаем объявление с помощью `Kaspi\DiContainer\diValue`:

```php
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;

use function Kaspi\DiContainer\diValue;

$container = (new DiContainerFactory())->make([
    'log' => diValue(['a' => 'aaa'])
]);

var_dump( ['a' => 'aaa'] === $container->get('log') ); // true
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
    App\SumInterface::class => diAutowire(App\Sum::class, ['init' => 50]),
    diAutowire(App\Sum::class =>, ['init' => 10], true),
];

$c = (new DiContainerFactory())->make($definition);
// … вызова определения
print $c->get(App\SumInterface::class)->init; // 50
print $c->get(App\Sum::class)->init; // 10
```
