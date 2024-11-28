# 🔑 DiContainer c конфигурированием через PHP атрибуты

[В конфигурации контейнера](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#конфигурирование_dicontainer) по умолчанию параметр `useAttribute` включён.

При конфигурировании контейнера можно совмещать php-атрибуты и php-определения.

> ⚠ При определении зависимостей в контейнере более высокой
> приоритет имеют php-атрибуты чем php-определения.

Доступные атрибуты:
- **[Inject](#inject)** - внедрение зависимости в аргументы конструктора, метода класса.
- **[Service](#service)** - определение для интерфейса какой класс будет вызван и разрешен в контейнере.
- **[DiFactory](#difactory)** - фабрика для c помощью которой разрешается зависимость класса. Класс должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`

## Inject

Применяется к аргументам конструктора класса, метода или функции.

```php
use Kaspi\DiContainer\Attributes\Inject;

#[Inject(
    id: '', // Определение зависимости (класс, интерфейс, идентификатор контейнера).
            // При пустом определении контейнер попытается получить
            // значение исходя из типа аргумента.
            // Если не удалось получить id по типу аргумента
            // контейнер попытается разрешить зависимость
            // по имени аргумента используя имя аргумента как идентификатор в контейнере.
)]
```

### Атрибут #[Inject] для получения по типу аргумента в конструкторе:

```php
// Объявление класса
namespace App;

use Kaspi\DiContainer\Attributes\Inject;

class MyDb {
    public function __construct(
        #[Inject]
        public \PDO $pdo
    ) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\diAutowire;

$definitions = [
    diAutowire(\PDO::class)
        ->addArgument('dsn', 'sqlite:/tmp/my.db')
];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\MyDb);
$myClass->pdo->query('...'); // аргумент $pdo указывать на dns = 'sqlite:/tmp/my.db'
```

### Атрибут #[Inject] для получения по идентификатору контейнера в конструкторе:

```php
// Объявление класса
use Kaspi\DiContainer\Attributes\Inject;

namespace App;

class MyDb {
    public function __construct(
        #[Inject('services.pdo')]
        public \PDO $pdo
    ) {}
}
```
```php
// file config/main.php
use function Kaspi\DiContainer\diAutowire;

return [
    'services.pdo-prod' => diAutowire(PDO::class)
        ->addArgument('dsn', 'sqlite:/data/prod/db.db'),
    'services.pdo-local' => diAutowire(PDO::class)
        ->addArgument('dsn', 'sqlite:/tmp/db.db'),
];
```
```php
// file config/db.php
use Psr\Container\ContainerInterface;
use function Kaspi\DiContainer\diCallable;

return [        
    'services.pdo' => diCallable(
        static fn (ContainerInterface $container) => match (\getenv('APP_PDO')) {
            'prod' => $container->get('services.pdo-prod'),
            default => $container->get('services.pdo-local')
        }
    ),
];
```
```php
$container = (new DiContainerFactory())->make(
    \array_merge(
        require 'config/main.php',
        require 'config/db.php',
    )
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
\putenv('APP_PDO=local');

// свойство $pdo будет указывать на базу sqlite:/tmp/db.db'
$myClass = $container->get(App\MyDb::class);
```

### Атрибут #[Inject] для разрешения аргументов переменной длины

Атрибут имеет признак `repetable`

```php
// Объявления классов
use Kaspi\DiContainer\Attributes\Inject;

namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}
class RuleB implements RuleInterface {}

class RuleGenerator {
    private iterable $rules;

    public function __construct(
        #[Inject(RuleB::class)]
        #[Inject(RuleA::class)]
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

#### Атрибут #[Inject] для аргументов переменной длины по идентификатору контейнера

```php
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}
class RuleB implements RuleInterface {}

class RuleGenerator {
    private iterable $rules;

    public function __construct(
        #[Inject('services.rules')]
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
use Kaspi\DiContainer\diCallable;

$definitions = [
    'services.rules' => diCallable(
        // Автоматически внедрит зависимости этой callback функции
        static function (App\Rules\RuleB::class $b, App\Rules\RuleA::class $a) {
            return [$b, $a]; // вернуть массив определений для аргумента переменной длины.
        }
    ),
];

$container = (new DiContainerFactory())->make($definitions);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
assert($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

### Атрибут #[Inject] и класс реализующий DiFactoryInterface
Класс реализующий `Kaspi\DiContainer\Interfaces\DiFactoryInterface` будет вызван контейнером и исполнен метод `__invoke`
который является результатом для Inject атрибута.

Пример применения для аргументов переменной длинны:
```php
// Объявления классов
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

namespace App\Rules;

interface RuleInterface {}
class RuleA implements RuleInterface {}
class RuleB implements RuleInterface {}

class RulesDiFactory implements DiFactoryInterface {
    public function __construct(
        private RuleA $ruleA,
        private RuleB $ruleB,
    ) {}

    public function __invoke(ContainerInterface $container): array {
        return [
            $this->ruleA,
            $this->ruleB,
        ];
    }
}

class RuleGenerator {
    private iterable $rules;

    public function __construct(
        #[Inject(RulesDiFactory::class)]
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

### Атрибут **#[Inject]** для внедрения по ссылке на другое определение в контейнере.

```php
// Объявления классов
namespace App\Rules;

interface RuleInterface {}

// ...

class RuleA implements RuleInterface {
    private array $config;

    public function __construct(private $dependency) {}

    public function setConfig(array $config): static {
        $this->config = $config;
        
        return $this;
    }
}

// ...

class RuleGenerator {
    public function __construct(
        #[Inject('service.rules.pre-config-rule-a')]
        public RuleInterface $inputRule
    ) {}
}
```
```php
// определения для контейнера
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\diCallable;

$definition = [
    diAutowire(App\Rules\RuleA::class)
        ->addArgument('dependency', '...'),

    // ...

    'service.rules.pre-config-rule-a' => diCallable(
        static function (App\Rules\RuleA $ruleA) {
            reutrn $ruleA->setConfig([...]);
        }
    ), 
];

$container = (new DiContainerFactory())->make($definition);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);
assert($ruleGenerator->inputRule instanceof App\Rules\RuleA); // true
```


## Service

Применяется к интерфейсу.

```php
use Kaspi\DiContainer\Attributes\Service;

#[Service(
    id: '', // Класс реализующий интерфейс
            // или идентификатор контейнера.
)]
```

```php
// Объявление классов
use Kaspi\DiContainer\Attributes\InjectByReference;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;

namespace App;

#[Service(CustomLogger::class)] // класс реализующий данный интерфейс.
interface CustomLoggerInterface {
    public function loggerFile(): string;
}

// Класс реализующий CustomLoggerInterface.

class CustomLogger implements CustomLoggerInterface {
    public function __construct(
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}

// Класс внедряющий зависимость по CustomLoggerInterface

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
use function Kaspi\DiContainer\diAutowire;

$definitions = [
    diAutowire(App\CustomLogger::class)
        ->addArgument('file', '/var/log/app.log')
];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\MyLogger::class);
print $myClass->customLogger->loggerFile(); // /var/log/app.log
```

Так же атрибут Service можно использовать со ссылкой на другой идентификатор контейнера.
```php
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\diCallable;

#[Service('services.my-srv')] // 🔃 Получить по идентификатору контейнера
interface MyInterface {}

// ...

class SuperClass {
    public function __construct(public MyInterface $my) {}
}

// ...

class SuperSrv implements MyInterface {
    public function changeConfig(array $config) {
        //...
    }
}

// ...
$definitions = [
    'services.my-srv' => diCallable(static function (SuperSrv $srv) {
        $srv->changeConfig([...]); // какие-то дополнительные настройки.
        
        return $srv; // вернуть настроенный сервис.
    }),
];

$container = (new DiContainerFactory())->make($definitions);

$container->get(SuperClass::class); 
```

## DiFactory
Применятся к классу для создания определения.
```php
use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(
    id: '', // Класс реализующий интерфейс Kaspi\DiContainer\Interfaces\DiFactoryInterface
    isSingleton: false,  // сервис создаётся как Singleton
)]
```

```php
// Определение класса
use Kaspi\DiContainer\Attributes\DiFactory

namespace App;

// Разрешить зависимость через фабрику и указать контейнеру что это будет Singleton.
#[DiFactory(App\Factory\FactorySuperClass::class, isSingleton: true)]
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
