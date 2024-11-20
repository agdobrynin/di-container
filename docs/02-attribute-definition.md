# 🔑 DiContainer c конфигурированием через PHP атрибуты

[В конфигурации контейнера](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#конфигурирование_dicontainer) по умолчанию параметр `useAttribute` включён.

При конфигурировании контейнера можно совмещать php-атрибуты и php-определения.

> ⚠ При определении зависимостей в контейнере более высокой
> приоритет имеют php-атрибуты чем php-определения.

Доступные атрибуты:
- **[InjectContext](#injectcontext)** - внедрение зависимости в аргументы конструктор или методы класса в контексте класса или метода.
- **[InjectByReference](#injectbyreference)** - внедрение зависимости по ссылке на другое определение в контейнере.
- **[Service](#service)** - определение для интерфейса какой класс будет вызван и разрешен в контейнере.
- **[ServiceByReference](#servicebyreference)** - определение для интерфейса по ссылке.
- **[DiFactory](#difactory)** - Фабрика для разрешения зависимостей. Класс должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`

## InjectContext

```php
use Kaspi\DiContainer\Attributes\InjectContext;

#[InjectContext(
    id: '', // Определение зависимости (класс, интерфейс).
            // При пустом определении контейнер попытается получить
            // значение исходя из типа аргумента.
            // Если не удалось получить определение по типу аргумента
            // контейнер попытается разрешить зависимость
            // по имени аргумента используя имя аргумента как идентификатор в контейнере.
    arguments: [], // Аргументы конструктора для зависимости
                   // переданные пользователем при конфигурировании.
    isSingleton: false,  // Сервис создаётся как Singleton.
)]
```

> 📝 Параметр "arguments" это аргументы, предоставленные пользователем.
>
> Каждый элемент в массиве аргументов должен содержать имя переменной в ключе и значении элемента.
>   
> ⚠ Если значение аргумента это строка и начинается с символа "@" то это будет обработано как ссылка на другое определение.
>
> ```php
> $arguments = [
>      // параметр строка
>      "paramNameOne" => "some value",
>      // ссылка на другое определение в контейнере
>      // сам символ определен как constant в 
>      // Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface::IS_REFERENCE
>      "paramNameTwo" => "@identifier",
>      "paramNameAny" => ..., // any types sucha as array,
>                             // object and other available types.
> ];
> ```
> 

### Получение существующего класса и разрешение простых типов параметров в конструкторе:

```php
// Объявление класса
namespace App;

use Kaspi\DiContainer\Attributes\InjectContext;

class MyClass {
    public function __construct(
        #[InjectContext(
            arguments: [
                // ⚠ префикс "@" в значении аргумента
                // указывает что данные для этого аргумента
                // нужно получить по ссылке.
                'dsn' => '@pdo_dsn',
            ],
            isSingleton: true 
        )]
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
### Внедрение типизированных аргументов через атрибут **InjectLocal**:

```php
// Объявление класса
namespace App;

use Kaspi\DiContainer\Attributes\InjectContext;

class MyUsers {
    public function __construct(public array $users) {}
}

class MyCompany {
    public function __construct(
        // аргумент id подставится автоматически и будет MyUsers
        #[InjectContext(arguments: ['users' => '@users_bosses'])]
        public MyUsers $bosses,
        #[InjectContext(arguments: ['users' => '@users_staffs'])]
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

### Атрибут **#[InjectContext]** для разрешения аргументов переменной длины

Атрибут имеет признак `repetable`

```php
// Объявления классов
namespace App\Rules;

use Kaspi\DiContainer\Attributes\InjectContext;

interface RuleInterface {}
class RuleA implements RuleInterface {}
class RuleB implements RuleInterface {}
class RuleC implements RuleInterface {}

class RuleGenerator {
    private iterable $rules;

    public function __construct(
        #[InjectContext(RuleB::class)]
        #[InjectContext(RuleA::class)]
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

$container = (new DiContainerFactory())->make();

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
assert($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

## **InjectByReference**

Внедрение по ссылке на другое определение в контейнере.

```php
use Kaspi\DiContainer\Attributes\InjectByReference;

#[InjectByReference(
    id: '', // имя определения в контейнере.
)]
```

Атрибут имеет признак `repetable`

```php
// Объявления классов
namespace App\Rules;

use Kaspi\DiContainer\Attributes\InjectByReference;

interface RuleInterface {}
class RuleA implements RuleInterface {}

class RuleGenerator {
    public function __construct(
        #[InjectByReference('service.rules.rule-a')] // получение по ссылке
        public RuleInterface $inputRule
    ) {}
}
```
```php
// определения для контейнера
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\DiContainerFactory;

$definition = [
    'service.rules.rule-a' => diAutowire(App\Rules\RuleA::class),
];

$container = (new DiContainerFactory())->make($definition);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->inputRule instanceof App\Rules\RuleA); // true
```


## Service

```php
use Kaspi\DiContainer\Attributes\Service;

#[Service(
    id: '', // Класс реализующий интерфейс.
            // Пустое значение попытка автоматически определить
            // тип аргумента.
    arguments: [], // Аргументы конструктора для зависимости.
    isSingleton: false,  // Сервис создаётся как Singleton.
)]
```

```php
// Объявление классов
namespace App;

use Kaspi\DiContainer\Attributes\InjectByReference;use Kaspi\DiContainer\Attributes\InjectContext;
use Kaspi\DiContainer\Attributes\Service;

#[Service(CustomLogger::class)] // класс реализующий данный интерфейс.
interface CustomLoggerInterface {
    public function loggerFile(): string;
}

// ....

class CustomLogger implements CustomLoggerInterface {
    public function __construct(
        #[InjectByReference('@logger_file')]
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}

// ...

class MyLogger {
    public function __construct(
        // Контейнер найдёт интерфейс
        // и проверит у него php-атрибут Service.
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

## ServiceByReference

todo...

## DiFactory

```php
use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(
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

Атрибут имеет признак `repetable`

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
