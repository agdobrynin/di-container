# 📦 DiContainer с конфигурированием на основе php-определений

Получение существующего класса и разрешение встроенных типов параметров в конструкторе:

```php
// Определения для DiContainer как array
use Kaspi\DiContainer\{DiContainer, DiContainerConfig};
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

$definitions = [
    \PDO::class => [
        // ⚠ Ключ "arguments" является зарезервированным значением
        // и служит для передачи в конструктор класса.
        // Таким объявлением в конструкторе класса \PDO
        // аргумент с именем $dsn получит значение
        // DiContainerInterface::ARGUMENTS = 'arguments'
        DiContainerInterface::ARGUMENTS => [
            'dsn' => 'sqlite:/opt/databases/mydb.sq3',
        ],
        // Сервис будет создан как Singleton - в течении
        // жизненного цикла контейнера. 
        DiContainerInterface::SINGLETON => true,
    ];
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
#### ⚠ Для увеличения производительности рекомендуется использовать функции-хэлперы для создания определений.

Пример определения описанный выше будет выглядеть так:
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
Функции-хэлперы определения имеют отложенную инициализацию параметров поэтому минимально влияют на начальную загрузку контейнера.

### Доступные функции-хэлперы для определения контейнера:

#### diAutowire
```php
diAutowire(string $definition, array $arguments = [], ?bool $isSingleton = null)
```
при конфигурировании не нужно указывать идентификатор контейнера - идентификатор сформируется из аргумента `$definition`

```php
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

```php
diCallable(array|callable|string $definition, array $arguments = [], ?bool $isSingleton = null)
```

#### diValue

```php
diValue(mixed $definition)
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

// При разрешении аргументов конструктора можно в качестве id контейнера
// использовать имя аргумента в конструкторе
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

## Внедрение значений зависимостей аргументов по ссылке на другой контейнер id.

Для внедрения зависимостей в аргуемнты по ссылке используется синтаксис `@container-id` -
где строка начинающаяся с символа `@` будет означать ссылку на другое определение
в контейнере, а часть `container-id` определение в контейнере.

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

// В объявлении arguments->users = "@data"
// будет искать в контейнере определение "data".

$definitions = [
    'data' => ['user1', 'user2'],
    
    // ... more definitions
    
    // внедрение зависимости аргумента по ссылке на контейнер-id
    diAutowire(App\MyUsers::class, ['users' => '@data']),
    diAutowire(App\MyEmployers::class, ['employers' => '@data'])
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
    $simpleDefinitions + $interfaceDefinition // simple merge or use function \array_merge
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
### Отдельное определение для класса и привязка интерфейса к реализациия для примера выше:

```php
// Определения для DiContainer - отдельно класс и реализации.
use App\ClassFirst;
use App\ClassInterface;
use Kaspi\DiContainer\DiContainerFactory;

use function Kaspi\DiContainer\diAutowire;

$classesDefinitions = [
    diAutowire(ClassFirst::class)
        ->addArgument('file', '/var/log/app.log')
];

// ... many definitions ...

$interfacesDefinitions = [
    ClassInterface::class => ClassFirst::class,
];

$container = (new DiContainerFactory()->make(
    $classesDefinitions + $interfacesDefinitions
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

$definitions = [
    App\MyClass::class => App\FactoryMyClass::class
];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$container->get(App\MyClass::class); // instance of App\MyClass
```
## `callable` тип как определение (definition).

Определения могут быть объявлены `callable` типом (см. [Callable](https://www.php.net/manual/ru/language.types.callable.php))

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

$container = (new DiContainerFactory())->make([
    diAutowire(App\ServiceLocation::class, ['city' => 'Vice city'])
    'doSomething' => diCallable(App\ClassWithStaticMethods::class.'::doSomething'),
]);
// получение данных
$expect === $container->get('doSomething'); // true
```

> 📝 Если у метода присутствуют аргументы, то они могут быть разрешены контейнером автоматически включая использование атрибутов _#[Inject]_, _#[DiFactory]_

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

$definition = [
    'ruleC' => App\Rules\RuleC::class,
    diAutowire(App\Rules\RuleGenerator::class)
        ->addArgument(
            name: 'inputRule', // имя аргумента в конструкторе
            value: [ // <-- обернуть параметры в массив для variadic типов
                App\Rules\RuleB::class,
                App\Rules\RuleA::class,
                '@ruleC', // <-- получение по ссылке
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

в таком случае делаем объявление с помощью `Kaspi\DiContainer\diValue`

```php
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionSimple;

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

class Sum {
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
// ... вызова определения
print $c->get(App\SumInterface::class)->init; // 50
print $c->get(App\Sum::class)->init; // 10
```
