# 🔑 DiContainer c конфигурированием через PHP атрибуты

[В конфигурации контейнера](https://github.com/agdobrynin/di-container/docs/01-php-definition.md) по умолчанию параметр `useAttribute` включён.

Доступные атрибуты:
- **Inject** - внедрение зависимости в аргументы конструктор или методы класса.
- **Service** - определение для интерфейса какой класс будет вызван и разрешен в контейнере.
- **DiFactory** - Фабрика для разрешения зависимостей. Класс должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`

## Inject

```php
#[\Kaspi\DiContainer\Attributes\Inject(
    id: '', // определение зависимости (класс, интерфейс, ссылка на контейнер)
            // если пустое определение то контейнер попытается получить значение по имени аргумента
    arguments: [], // аргументы конструктора для зависимости
    isSingleton: false,  // сервис создаётся как Singleton
)]
```

### Получение существующего класса и разрешение простых типов параметров в конструкторе:

```php
// Объявление класса
namespace App;

use Kaspi\DiContainer\Attributes\Inject;

class MyClass {
    public function __construct(
        #[Inject(arguments: ['dsn' => '@pdo_dsn'])]
        public \PDO $pdo
    ) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$definitions = ['pdo_dsn' => 'sqlite:/opt/databases/mydb.sq3'];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```

### Использование **Inject** атрибута на простых (встроенных) типах для получения данных из контейнера:

```php
// Объявление класса
namespace App;

use Kaspi\DiContainer\Attributes\Inject;

class MyUsers {
    public function __construct(
        // ссылка на контейнер с определением
        #[Inject('@users_data')]
        public array $users
    ) {}
}

class MyEmployers {
    public function __construct(
        // ссылка на контейнер с определением
        #[Inject('@users_data')]
        public array $employers
    ) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$definitions = [
    'users_data' => ['user1', 'user2'],
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

### Внедрение типизированных аргументов через атрибут **Inject**:

```php
// Объявление класса
namespace App;

use Kaspi\DiContainer\Attributes\Inject;

class MyUsers {
    public function __construct(public array $users) {}
}

class MyCompany {
    public function __construct(
        #[Inject(arguments: ['users' => '@users_bosses'])]
        public MyUsers $bosses,
        #[Inject(arguments: ['users' => '@users_staffs'])]
        public MyUsers $staffs,
    ) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$definitions = [
    'users_bosses' => ['user1', 'user2'],
    'users_staffs' => ['user3', 'user3'],
];

$container = (new DiContainerFactory())->make($definitions);
```
```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyCompany;

/** @var MyCompany::class $company */
$company = $container->get(MyCompany::class);
print implode(',', $company->bosses->users); // user1, user2
print implode(',', $company->staffs->users); // user3, user4
```

### Атрибут **#[Inject]** для разрешения аргументов переменной длины

атририбут имеет признак `repetable`

```php
// Объявления классов
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Inject;

interface RuleInterface {}
class RuleA implements RuleInterface {}
class RuleB implements RuleInterface {}
class RuleC implements RuleInterface {}

class RuleGenerator {
    private iterable $rules;

    public function __construct(
        #[Inject(RuleB::class)]
        #[Inject(RuleA::class)]
        #[Inject('@ruleC')] // получение по ссылке
        RuleInterface ...$inputRule
    ) {
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
];

$container = (new DiContainerFactory())->make($definition);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
assert($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
assert($ruleGenerator->getRules()[2] instanceof App\Rules\RuleС); // true
```

## Service

```php
#[\Kaspi\DiContainer\Attributes\Service(
    id: '', // Класс реализующий интерфейс или ссылка на контейнер класса реализующий интерфейс
    arguments: [], // аргументы конструктора для зависимости
    isSingleton: false,  // сервис создаётся как Singleton
)]
```

```php
// Объявление классов
namespace App;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;

#[Service(CustomLogger::class)]
interface CustomLoggerInterface {
    public function loggerFile(): string;
}

class CustomLogger implements CustomLoggerInterface {
    public function __construct(
        #[Inject('@logger_file')]
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}

// ...

class MyLogger {
    public function __construct(
        #[Inject]
        public CustomLoggerInterface $customLogger
    ) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make(
    definitions: ['logger_file' => '/var/log/app.log']
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyLogger;

/** @var MyLogger $myClass */
$myClass = $container->get(MyLogger::class);
print $myClass->customLogger->loggerFile(); // /var/log/app.log
```

## DiFactory

```php
#[\Kaspi\DiContainer\Attributes\DiFactory(
    id: '', // Класс реализующий интерфейс Kaspi\DiContainer\Interfaces\DiFactoryInterface
    arguments: [], // аргументы конструктора для зависимости
    isSingleton: false,  // сервис создаётся как Singleton
)]
```

```php
// Определение класса
namespace App;

#[Factory(App\Factory\FactorySuperClass::class)]
class SuperClass
{
    public function __construct(public string $name, public int $age) {}
}
```

```php
// определение фабрики
namespace App\Factory;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class FactorySuperClass implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): App\SuperClass
    {
        return new App\SuperClass('Piter', 22);
    }
}
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\SuperClass;

/** @var SuperClass $myClass */
$myClass = $container->get(SuperClass::class);
print $myClass->name; // Piter
print $myClass->age; // 22
```

### Использование #[DiFactory] для аргументов

Так же можно использовать атрибут **Factory** для аргументов конструктора или методов класса:

```php
// определение класса
namespace App;

use Kaspi\DiContainer\Attributes\DiFactory;

class ClassWithFactoryArgument
{
    public function __construct(
        #[DiFactory(FactoryClassWithFactoryArgument::class)]
        public \ArrayIterator $arrayObject
    ) {}
}
```

```php
// Фабрика класса
namespace App;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class FactoryClassWithFactoryArgument implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): \ArrayIterator
    {
        return new \ArrayIterator(
            $container->has('names') ? $container->get('names') : []
        );
    }
}
```

```php
// Определение для контейнера
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make(
    definitions: [
        'names' => ['Ivan', 'Piter', 'Vasiliy']
    ]
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\ClassWithFactoryArgument;

/** @var ClassWithFactoryArgument $myClass */
$myClass = $container->get(ClassWithFactoryArgument::class);
$myClass->arrayObject->getArrayCopy(); // массив ['Ivan', 'Piter', 'Vasiliy']
```

### Атрибут #[DiFactory] для разрешения аргументов переменной длины

атририбут имеет признак `repetable`

```php
// Объявления классов
namespace App\Rules;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

interface RuleInterface {}

class RuleA implements RuleInterface {
    // some logic here
}

class RuleAFactory implements DiFactoryInterface {
    public function __invoke(ContainerInterface $container): RuleA {
        // some logic for creating class
        return new RuleA();
    }
}

class RuleB implements RuleInterface {
    // some logic here
}


class RuleBFactory implements DiFactoryInterface {
    public function __invoke(ContainerInterface $container): RuleB {
        // some logic for creating class
        return new RuleB();
    }
}

class RuleGenerator {
    private iterable $rules;

    public function __construct(
        #[DiFactory(RuleAFactory::class)]
        #[DiFactory(RuleBFactory::class)]
        RuleInterface ...$inputRule
    ) {
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

$container = (new DiContainerFactory())->make($definition);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->getRules()[0] instanceof App\Rules\RuleA); // true
assert($ruleGenerator->getRules()[1] instanceof App\Rules\RuleB); // true
```
