# DiContainer

Kaspi/di-container — это легковесный контейнер внедрения зависимостей для PHP >= 8.0 с автоматическим связыванием зависимостей.

## Установка

```shell
composer require kaspi/di-container
```

#### Миграция с версии 1.0.x к версии 1.1.x

Новая сигнатура интерфейса `DiContainerFactoryInterface` для метод `make`:

```php
// Для версий 1.0.x
$container = DiContainerFactory::make($definitions);
// Для версий 1.1.х и выше
$container = (new DiContainerFactory())->make($definitions);
```

### Примеры использования

* Примеры использования пакета kaspi/di-container в [репозитории](https://github.com/agdobrynin/di-container-examples) 🦄
* Примеры использования [DiContainer с конфигурированием на основе php-определений](#DiContainer-с-конфигурированием-на-основе-php-определений).
* Примеры использования [DiContainer c конфигурированием через PHP атрибуты](#DiContainer-c-конфигурированием-через-PHP-атрибуты).
* Конфигурация DiContainer [с использованием нотаций по массиву](#Access-array-delimiter-notation).
* [Конфигурирование Autowired](#Конфигурирование-Autowired)

#### DiContainer с конфигурированием на основе php-определений

Получение существующего класса и разрешение встроенных типов параметров в конструкторе:
```php
// Определения для DiContainer
use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
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
        ];
    ];

$autowire = new Autowired(useAttribute: false);
$container = new DiContainer(definitions: $definitions, autowire: $autowire);
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

Разрешение встроенных (простых) типов аргументов в объявлении:

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
use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

// В объявлении arguments->users = "data"
// будет искать в контейнере ключ "data".

$definitions = [
    'data' => ['user1', 'user2'],
    
    // ... more definitions
    
    App\MyUsers::class => [
        DiContainerInterface::ARGUMENTS => [
            'users' => 'data',
        ],
    ],
    App\MyEmployers::class => [
        DiContainerInterface::ARGUMENTS => [
            'employers' => 'data',
        ],
    ],
];

$container = new DiContainer(definitions: $definitions, autowire: new Autowired());
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

Разрешение встроенных (простых) типов аргументов в объявлении со ссылкой на другой id контейнера:

```php
// Определения для DiContainer
use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

// В конструкторе DiContainer - параметр "linkContainerSymbol"
// определяет символ с которого начинается строка и будет
// обработана как ссылка на "id" контейнера
// по умолчанию символ "@"

$definitions = [
        // основной id в контейнере
        'sqlite-current-dsn' => 'sqlite:/opt/databases/mydb.sq3',
        //.....
        // Id в контейнере содержащий ссылку на id контейнера = "sqlite-home"
        'sqlite-dsn' => '@sqlite-current-dsn',
        \PDO::class => [
            DiContainerInterface::ARGUMENTS => [
                'dsn' => '@sqlite-dsn',
            ],
        ];
    ];

$container = new DiContainer(definitions: $definitions, autowire: new Autowired());
```

```php
// Объявление класса
namespace App;

class MyClass {
    public function __construct(public \PDO $pdo) {}
}

// ....

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
// в конструктор MyClass будет вызван с определением
// new MyClass(
//      pdo: new \PDO(dsn: 'sqlite:/opt/databases/mydb.sq3') 
// );
```

Разрешение типов аргументов в конструкторе по имени аргумента:

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

Получение класса по интерфейсу
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
use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
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

$container = new DiContainer(
    definitions: $definitions,
    autowire: new Autowired(useAttribute: false)
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyLogger;

/** @var MyClass $myClass */
$myClass = $container->get(MyLogger::class);
$myClass->logger()->debug('...');
```

Ещё один пример получение класса по интерфейсу:

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
use Kaspi\DiContainer\{Autowired, DiContainer};

$container = new DiContainer(autowire: new Autowired(useAttribute: false));

// ⚠ параметр "arguments" метода "set" установить аргументы для конструктора.
$container->set(ClassFirst::class, arguments: ['file' => '/var/log/app.log']);
$container->set(ClassInterface::class, ClassFirst::class);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\ClassInterface;

/** @var ClassFirst $myClass */
$myClass = $container->get(ClassInterface::class);
print $myClass->file; // /var/log/app.log
```

🧙‍♂️ **Разрешение зависимости в контейнере с помощью фабрики**.

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
    public function __invoke(ContainerInterface $container): MyClass
    {
        return new MyClass(new Db(...));
    }    
}
```

```php
// определения для контейнера
use Kaspi\DiContainer\{Autowired, DiContainer};

$definitions = [
    App\MyClass::class => App\FactoryMyClass::class
];

$container = new DiContainer(
    definitions: $definitions,
    autowire: new Autowired(useAttribute: false)
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$container->get(App\MyClass::class); // instance of App\MyClass
```

#### DiContainer c конфигурированием через PHP атрибуты

Конфигурирование DiContainer c PHP атрибутами для определений.

Доступные атрибуты:
- **Inject** - внедрение зависимости в аргументы конструктор или методы класса.
- **Service** - определение для интерфейса какой класс будет вызван и разрешен в контейнере.
- **Factory** - Фабрика для разрешения зависимостей.
Класс должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`
 
[В классе Autowired](#Конфигурирование-Autowired) по умолчанию параметр `useAttribute` включен:

```php
new \Kaspi\DiContainer\Autowired(useAttribute: true);
```

Получение существующего класса и разрешение простых типов параметров в конструкторе:

```php
// Объявление класса
namespace App;

use Kaspi\DiContainer\Attributes\Inject;

class MyClass {
    public function __construct(
        #[Inject(arguments: ['dsn' => 'pdo_dsn'])]
        public \PDO $pdo
    ) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\{Autowired, DiContainer};

$container = new DiContainer(
    definitions: ['pdo_dsn' => 'sqlite:/opt/databases/mydb.sq3'],
    autowire: new Autowired(useAttribute: true) 
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```

Использование **Inject** атрибута на простых (встроенных) типах для  
получения данных из контейнера, где ключ "users_data" определен в контейнере:

```php
// Объявление класса
namespace App;

use Kaspi\DiContainer\Attributes\Inject;

class MyUsers {
    public function __construct(
        #[Inject('users_data')]
        public array $users
    ) {}
}

class MyEmployers {
    public function __construct(
        #[Inject('users_data')]
        public array $employers
    ) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\{Autowired, DiContainer};

$definitions = [
    'users_data' => ['user1', 'user2'],
];

$container = new DiContainer(
    definitions: $definitions,
    autowire: new Autowired(useAttribute: true) 
);
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

Получение по интерфейсу с использованием атрибута **Service**:

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
        #[Inject('logger_file')]
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
use Kaspi\DiContainer\{Autowired ,DiContainer};

$container = new DiContainer(
    definitions: ['logger_file' => '/var/log/app.log'],
    autowire: new Autowired(useAttribute: true) 
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyLogger;

/** @var MyLogger $myClass */
$myClass = $container->get(MyLogger::class);
print $myClass->customLogger->loggerFile(); // /var/log/app.log
```

Использование атрибута **Factory** для разрешения класса

```php
// Определение класса
namespace App;

#[Factory(\App\Factory\FactorySuperClass::class)]
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
use Kaspi\DiContainer\{Autowired, DiContainer};

$container = new DiContainer(
    definitions: [
        'names' => ['Ivan', 'Piter', 'Vasiliy']
    ],
    autowire: new Autowired(useAttribute: true) 
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\ClassWithFactoryArgument;

/** @var ClassWithFactoryArgument $myClass */
$myClass = $container->get(ClassWithFactoryArgument::class);
$myClass->arrayObject->getArrayCopy(); // массив ['Ivan', 'Piter', 'Vasiliy']
```

#### Access array delimiter notation

Доступ к "контейнер-id" с вложенными определениям.

Такая ссылка определяется в связке с определением `"linkContainerSymbol"` и `"delimiterAccessArrayNotationSymbol"`

Произвольный символ разделитель можно определить

* `Kaspi\DiContainer\DiContainer::__construct` аргумент `$delimiterAccessArrayNotationSymbol`
* `Kaspi\DiContainer\DiContainerFactory::make` аргумент `$delimiterAccessArrayNotationSymbol`


> по-умолчанию 
>   * "linkContainerSymbol" = "@"
>   * "delimiterAccessArrayNotationSymbol" = "."

```php
    return [
        'a' => [
            'b' => [
                'c' => 'Hello world'
            ],
        ],
        // ... more definitions
        'container-id' => '@a.b.c'
    ];

// ... 

print $contaier->get('container-id'); // Hello world
```

###### Access-array-delimiter-notation определение на базе ручного конфигурирования

```php
// Определения для DiContainer
use \Kaspi\DiContainer\Interfaces\DiContainerInterface;

$definitions = [
    'app' => [
        'admin' => [
            'email' =>'admin@mail.com',
        ],
        'logger' => App\Logger::class,
        'logger_file' => '/var/app.log',
    ],
    App\Logger::class => [
        DiContainerInterface::ARGUMENTS => [
            'file' => '@app.logger_file'
        ],
    ],
    App\SendEmail::class => [
        DiContainerInterface::ARGUMENTS => [
            'from' => '@app.admin.email',
            'logger' => '@app.logger',
        ],
    ],
];

$container = DiContainerFactory::make($definitions);
```
```php
// Объявление классов
namespace App;

interface LoggerInterface {}

class Logger implements LoggerInterface {
    public function __construct(
        public string $file
    ) {}
}

class SendEmail {
    public function __construct(
        public string $from,
        public LoggerInterface $logger,
    ) {}
}
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\SendEmail;

/** @var SendEmail $myClass */
$sendEmail = $container->get(SendEmail::class);
print $sendEmail->from; // admin@mail.com
print $sendEmail->logger->file; // /var/app.log
```

###### Access-array-delimiter-notation - определения на основе PHP атрибутов.

```php
// Определения для DiContainer
$definitions = [
    'app' => [
        'admin' => [
            'email' =>'admin@mail.com',
        ],
        'logger' => App\Logger::class,
        'logger_file' => '/var/app.log',
    ],
];

$container = DiContainerFactory::make($definitions);
```

```php
// Объявление классов
namespace App;

use Kaspi\DiContainer\Attributes\Inject;

interface LoggerInterface {}

class Logger implements LoggerInterface {
    public function __construct(
        #[Inject('@app.logger_file')]
        public string $file
    ) {}
}

class SendEmail {
    public function __construct(
        #[Inject('@app.admin.email')]
        public string $from,
        #[Inject('@app.logger')]
        public LoggerInterface $logger,
    ) {}
}
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\SendEmail;

/** @var SendEmail $myClass */
$sendEmail = $container->get(SendEmail::class);
print $sendEmail->from; // admin@mail.com
print $sendEmail->logger->file; // /var/app.log
```

#### Конфигурирование Autowired

Компонент **Autowired** предназначен для автоматического разрешения зависимостей на основе
контейнера реализующего интерфейс - `Psr\Container\ContainerInterface`.

По умолчанию разрешение зависимостей работает на основе [php-определений](#DiContainer-с-конфигурированием-на-основе-php-определений) и на php-атрибутах в классах и интерфейсах кода.

Доступность [php-атрибутов](#DiContainer-c-конфигурированием-через-PHP-атрибуты) определяется параметром в конструкторе:

```php
// Отключить проверку php-атрибутов для разрешения зависимостей.
$autowire = new \Kaspi\DiContainer\Autowired(useAttribute: false);
$container = new \Kaspi\DiContainer\DiContainer(autowire: $autowire)
```

* по умолчанию параметр `useAttribute` = `true`.

## Тесты
Прогнать тесты без подсчета покрытия кода
```shell
composer test
```
Запуск тестов с проверкой покрытия кода тестами
```shell
./vendor/bin/phpunit
```

## Статический анализ кода

Для статического анализа используем пакет [Phan](https://github.com/phan/phan).

Запуск без PHP расширения [PHP AST](https://github.com/nikic/php-ast)

```shell
./vendor/bin/phan --allow-polyfill-parser
```

## Code style
Для приведения кода к стандартам используем php-cs-fixer который объявлен 
в dev зависимости composer-а

```shell
composer fixer
``` 

## Использование Docker образа с PHP 8.0, 8.1, 8.2, 8.3

Указать образ с версией PHP можно в файле `.env` в ключе `PHP_IMAGE`. 
По умолчанию контейнер собирается с образом `php:8.0-cli-alpine`.

Собрать контейнер
```shell
docker-compose build
```
Установить зависимости php composer-а:
```shell
docker-compose run --rm php composer install
```
Прогнать тесты с отчетом о покрытии кода
```shell
docker-compose run --rm php vendor/bin/phpunit
```
⛑ pезультаты будут в папке `.coverage-html`

Статический анализ кода Phan (_static analyzer for PHP_)

```shell
docker-compose run --rm php vendor/bin/phan
```

Можно работать в shell оболочке в docker контейнере:
```shell
docker-compose run --rm php sh
```
