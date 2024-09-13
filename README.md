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
* Примеры использования [DiContainer со стандартным конфигурированием](#DiContainer-со-стандартным-конфигурированием).
* Примеры использования [DiContainer c PHP атрибутами](#DiContainer-c-PHP-атрибутами).
* Конфигурация DiContainer [с использованием нотаций по массиву](#Access-array-delimiter-notation).

#### DiContainer со стандартным конфигурированием

Через определения зависимостей вручную в DiContainer.

Получение существующего класса и разрешение встроенных типов параметров в конструкторе:
```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make(
    [
        \PDO::class => [
            // ⚠ Ключ "arguments" является зарезервированным значением
            // и служит для передачи значений в конструктор класса.
            // Таким объявлением в конструкторе класса \PDO
            // аргумент с именем $dsn получит значение
            'arguments' => [
                'dsn' => 'sqlite:/opt/databases/mydb.sq3',
            ],
        ];
    ]
);
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
use Kaspi\DiContainer\DiContainerFactory;

// В объявлении arguments->users = "data"
// будет искать в контейнере ключ "data".

$definitions = [
    'data' => ['user1', 'user2'],
    App\MyUsers::class => [
        'arguments' => [
            'users' => 'data',
        ],
    ],
    App\MyEmployers::class => [
        'arguments' => [
            'employers' => 'data',
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

Разрешение встроенных (простых) типов аргументов в объявлении со ссылкой на другой id контейнера:

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

// В конструкторе DiContainer - параметр "linkContainerSymbol"
// определяет значение-ссылку для авто связывания аргументов -
// по умолчанию символ "@"

$container = (new DiContainerFactory())->make(
    [
        // основной id в контейнере
        'sqlite-home' => 'sqlite:/opt/databases/mydb.sq3',
        //.....
        // Id в контейнере содержащий ссылку на id контейнера = "sqlite-home"
        'sqlite-test' => '@sqlite-home',
        \PDO::class => [
            'arguments' => [
                'dsn' => 'sqlite-test',
            ],
        ];
    ]
);
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
use Kaspi\DiContainer\DiContainerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\{Logger, Handler\StreamHandler, Level};

$container = (new DiContainerFactory())->make([
    'logger.file' => '/path/to/your.log',
    'logger.name' => 'app-logger',
    LoggerInterface::class =>, static function (ContainerInterface $c) {
        return (new Logger($c->get('logger.name')))
            ->pushHandler(new StreamHandler($c->get('logger.file')));
    }
])
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
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();
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

🎭 Использование метода `__invoke` класса для разрешения зависимости в контейнере:

```php
// Объявление класса
namespace App;

class SomeDependency { }

class Invokable {
    public function __invoke(SomeDependency $dependency) {}
}
```
```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;
use Psr\Container\ContainerInterface;

$container = (new DiContainerFactory(
    definitions: [
        App\Invokable::class => static function (
            ContainerInterface $c,
            App\Invokable $invokable
        ) {
            return $invokable($c->get(App\SomeDependency::class))
        }
    ]
))->make();
```

```php
// Получение данных из контейнера
use App\Invokable;

/** @var Invokable $res */
$result = $container->get(App\Invokable::class);
```
#### DiContainer c PHP атрибутами

Конфигурирование DiContainer c PHP атрибутами для определений.

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
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make(
    ['pdo_dsn' => 'sqlite:/opt/databases/mydb.sq3']
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```

Использование Inject атрибута на простых (встроенных) типах для  
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

Получение по интерфейсу:

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
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make([
    'logger_file' => '/var/log/app.log'
]);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyLogger;

/** @var MyLogger $myClass */
$myClass = $container->get(MyLogger::class);
print $myClass->customLogger->loggerFile(); // /var/log/app.log
```
#### Access array delimiter notation

Доступ к "контейнер-id" с вложенными определениям.

По-умолчанию символ разделитель `.`

Произвольный символ разделитель можно определить

* `Kaspi\DiContainer\DiContainer::__construct` аргумент `$delimiterAccessArrayNotationSymbol` 
* `Kaspi\DiContainer\DiContainerFactory::make` аргумент `$delimiterAccessArrayNotationSymbol`


###### Access-array-delimiter-notation определение на базе ручного конфигурирования

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
    App\Logger::class => [
        'arguments' => [
            'file' => 'app.logger_file'
        ],
    ],
    App\SendEmail::class => [
        'arguments' => [
            'from' => 'app.admin.email',
            'logger' => 'app.logger',
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
        #[Inject('app.logger_file')]
        public string $file
    ) {}
}

class SendEmail {
    public function __construct(
        #[Inject('app.admin.email')]
        public string $from,
        #[Inject('app.logger')]
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
