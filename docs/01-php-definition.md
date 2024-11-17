### DiContainer с конфигурированием на основе php-определений

Получение существующего класса и разрешение встроенных типов параметров в конструкторе:

```php
// Определения для DiContainer
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
Разрешение типов аргументов в конструкторе по имени:

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
$container = (new DiContainerFactory())->make(
    [
        'listOfUsers' => [
            'John',
            'Arnold',
        ];
    ]
);
```
```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyUsers;

/** @var MyUsers::class $users */
$users = $container->get(MyUsers::class);
print implode(',', $users->users); // John, Arnold
```

#### Внедрение значений зависимостей аргументов по ссылке на другой контейнер id.

Для внедрения зависимостей в аргуемнты по ссылке используется синтаксис `@container-id` -
где строка начинающаяся с символа `@` будет означать ссылку на другое определение
в контейнере, а часть `container-id` определение в контейнере.

Разрешение простых (builtin) типов аргументов в объявлении:

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
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

// В объявлении arguments->users = "@data"
// будет искать в контейнере определение "data".

$definitions = [
    'data' => ['user1', 'user2'],
    
    // ... more definitions
    
    App\MyUsers::class => [
        DiContainerInterface::ARGUMENTS => [
            // внедрение зависимости аргумента по ссылке на контейнер-id
            'users' => '@data',
        ],
    ],
    App\MyEmployers::class => [
        DiContainerInterface::ARGUMENTS => [
            // внедрение зависимости аргумента по ссылке на контейнер-id
            'employers' => '@data',
        ],
    ],
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

#### Получение класса по интерфейсу

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

$definitions = [
    'logger_file' => '/path/to/your.log',
    'logger_name' => 'app-logger',
    LoggerInterface::class =>, static function (ContainerInterface $c) {
        return (new Logger($c->get('logger_name')))
            ->pushHandler(new StreamHandler($c->get('logger_file')));
    }
];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyLogger;

/** @var MyClass $myClass */
$myClass = $container->get(MyLogger::class);
$myClass->logger()->debug('...');
```

Получение через объявления в контейнере:

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
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

$definition = [
    ClassInterface::class => [
        ClassFirst::class,
        DiContainerInterface::ARGUMENTS => [
            'file' => '/var/log/app.log',
        ]
    ],
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
Отдельное определение для класса и приявязка интерфейса к реализациия для примера выше:

```php
// Определения для DiContainer - отдельно класс и реализации.
use App\ClassFirst;
use App\ClassInterface;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

$definition = [
    ClassFirst::class => [
        DiContainerInterface::ARGUMENTS => [
            'file' => '/var/log/app.log',
        ],    
    ],
    ClassInterface::class => ClassFirst::class,
];

$container = (new DiContainerFactory()->make($definition);
```

#### 🧙‍♂️ Разрешение зависимости в контейнере с помощью фабрики.

Класс фабрика должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`.

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
#### `callable` тип как определение (definition).

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
use Kaspi\DiContainer\Interfaces\{DiContainerFactory, DiContainerInterface};

$expect = (object) ['name' => 'John Doe', 'age' => 32, 'gender' => 'male', 'city' => 'Vice city'];

$container = (new DiContainerFactory())->make([
    App\ServiceLocation::class => [
        DiContainerInterface::ARGUMENTS => ['city' => 'Vice city'],
     ],
    'doSomething' => App\ClassWithStaticMethods::class.'::doSomething',
]);
// получение данных
$expect === $container->get('doSomething'); // true
```

> 📝 Если у метода присутствуют аргументы, то они могут быть разрешены контейнером автоматически включая использование атрибутов _#[Inject]_, _#[DiFactory]_

#### Разрешение аргументов переменной длины

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

$definition = [
    'ruleC' => App\Rules\RuleC::class,
    App\Rules\RuleGenerator::class => [
        DiContainerInterface::ARGUMENTS => [
            'inputRule' => [ // <-- обернуть параметры в массив
                App\Rules\RuleB::class,
                App\Rules\RuleA::class,
                '@ruleC', // <-- получение по ссылке
            ], // <-- обернуть параметры в массив
        ]
    ],
];

$container = (new DiContainerFactory())->make($definition);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
assert($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
assert($ruleGenerator->getRules()[2] instanceof App\Rules\RuleС); // true
```
#### Определения реализующие интерфейс DiDefinitionInterface

В некоторых случая необходимо конфигурирование контейнера используя определения реализующие
интерфейс `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface` так как алгоритм
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

в таком случае делаем объявление с помощью класса `Kaspi\DiContainer\DiDefinition\DiDefinitionSimple`

```php
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionSimple;

$container = (new DiContainerFactory())->make([
    'log' => new DiDefinitionSimple(['a' => 'aaa']),
]);

var_dump( ['a' => 'aaa'] === $container->get('log') ); // true
```

##### Функция-хэлпер для удобства конфигурирования контейнера:

```php
Kaspi\DiContainer\diDefinition(?string $containerKey = null, mixed $definition = null, ?array $arguments = null, ?bool $isSingleton = null): array
```

Пример использования хэлпера для конфигурирования:
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

$definition = [
    App\SumInterface::class => diDefinition(definition: App\Sum::class, arguments: ['init' => 50]),
    App\Sum::class => diDefinition(arguments: ['init' => 10], isSingleton: true),
];

$c = (new DiContainerFactory())->make($definition);
// ... вызова определения
print $c->get(App\SumInterface::class)->init; // 50
print $c->get(App\Sum::class)->init; // 10
```
альтернативное объявление определений:
```php
use \Kaspi\DiContainer\diDefinition;

$definition1 = diDefinition(
    containerKey: App\SumInterface::class,
    definition: App\Sum::class,
    arguments: ['init' => 50]
);

$definition2 = diDefinition(
    containerKey: App\Sum::class,
    arguments: ['init' => 10],
    isSingleton: true  
);

$c = (new DiContainerFactory())->make($definition1 + $definition2);
```
