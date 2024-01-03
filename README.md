# DiContainer

Kaspi/di-container — это легковесный контейнер внедрения зависимостей для PHP >= 8.0 с автоматическим связыванием зависимостей.

## Установка

```shell
composer require kaspi/di-container
```

### Примеры использования

* Примеры использования пакета kaspi/di-container в [репозитории](https://github.com/agdobrynin/di-container-examples) 🦄
* Примеры использования [DiContainer](#Конфигурирование)
* Примеры использования [DiContainer c PHP атрибутами](#PHP-attributes)

#### Конфигурирование
Через определения зависимостей вручную в DiContainer.

Получение существующего класса и разрешение встроенных типов параметров в конструкторе:
```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$container = DiContainerFactory::make(
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

$container = DiContainerFactory::make($definitions);
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

$container = DiContainerFactory::make(
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
$container = DiContainerFactory::make(
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
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\{Logger, Handler\StreamHandler, Level};

$container = DiContainerFactory::make()
$container->set('logger.file', '/path/to/your.log');
$container->set('logger.name', 'app-logger');
$container->set(
    LoggerInterface::class,
    static function (DiContainerInterface $c) {
        return (new Logger($c->get('logger.name')))
            ->pushHandler(new StreamHandler($c->get('logger.file')));
    }
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
use Kaspi\DiContainer\DiContainerFactory;

$container = DiContainerFactory::make();
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

#### PHP-attributes

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

$container = DiContainerFactory::make(
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

$container = DiContainerFactory::make($definitions);
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

$container = DiContainerFactory::make([
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


## Тесты
Прогнать тесты без подсчета покрытия кода
```shell
composer test
```
Запуск тестов с проверкой покрытия кода тестами
```shell
./vendor/bin/phpunit
```

## Code style
Для приведения кода к стандартам используем php-cs-fixer который объявлен 
в dev зависимости composer-а

```shell
composer fixer
``` 

## Использование Docker образа с PHP 8.0

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

Можно работать в shell оболочке в docker контейнере:
```shell
docker-compose run --rm php sh
```
