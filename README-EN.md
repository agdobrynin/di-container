# DiContainer

[README in russian language](https://github.com/agdobrynin/di-container/README.md)


**Kaspi/di-container** is a lightweight dependency injection container for PHP >= 8.0 with automatic dependency bundling.
## Install

```shell
composer require kaspi/di-container
```

#### Migration from version 1.0.x to 1.1.x

New interface signature `DiContainerFactoryInterface` for the `make` method:
```php
// For version 1.0.x
$container = DiContainerFactory::make($definitions);
// For versions 1.1.x and higher
$container = (new DiContainerFactory())->make($definitions);
```

### Examples of using

* Using the kaspi/di-container package in [the repository](https://github.com/agdobrynin/di-container-examples) 🦄
* Examples of use [DiContainer with standard configuration](#dicontainer-with-standard-configuration).
* Examples of using [DiContainer with PHP attributes](#dicontainer-with-php-attributes).
* DiContainer Configuration [using array notation](#Access-array-delimiter-notation).

#### DiContainer with standard configuration

Through manual dependency definitions in DiContainer.

Getting an existing class and allowing built-in parameter types in the constructor:
```php
// Definitions for DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make(
    [
        \PDO::class => [
             // ⚠ The "arguments" key is a reserved value
             // and serves to pass values to the class constructor.
             // With this declaration in the constructor of the class \PDO
             // argument named $dsn will receive the value
            'arguments' => [
                'dsn' => 'sqlite:/opt/databases/mydb.sq3',
            ],
        ];
    ]
);
```

```php
// Class declaration
namespace App;

class MyClass {
    public function __construct(public \PDO $pdo) {}
}
```

```php
// Retrieving data from a container with automatic dependency binding
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```

Autowiring built-in (simple) argument types in a declaration:

```php
// Class definition
namespace App;

class MyUsers {
    public function __construct(public array $users) {}
}

class MyEmployers {
    public function __construct(public array $employers) {}
}
```

```php
// Definitions for DiContainer
use App\{MyUsers, MyEmployers};
use Kaspi\DiContainer\DiContainerFactory;

// In the declaration arguments->users = "data"
// will look for the "data" key in the container.

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
// Retrieving data from the container with automatic dependency binding
use App\{MyUsers, MyEmployers};

/** @var MyUsers::class $users */
$users = $container->get(MyUsers::class);
print implode(',', $users->users); // user1, user2
/** @var MyEmployers::class $employers */
$employers = $container->get(MyEmployers::class);
print implode(',', $employers->employers); // user1, user2
```

Autowirung (simple) argument types in a declaration with a reference to another container id:
```php
// Definitions for DiContainer
use Kaspi\DiContainer\DiContainerFactory;

// In the DiContainer constructor - the "linkContainerSymbol" parameter
// defines a reference value for autowiring arguments -
// default symbol is "@"

$container = (new DiContainerFactory())->make(
    [
        // main id in the container
        'sqlite-home' => 'sqlite:/opt/databases/mydb.sq3',
        //.....
        // Id in the container containing a link to the container id = "sqlite-home"
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
// Class declaration
namespace App;

class MyClass {
    public function __construct(public \PDO $pdo) {}
}

// ....

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
// in the MyClass constructor will be called with the definition
// new MyClass(
//      pdo: new \PDO(dsn: 'sqlite:/opt/databases/mydb.sq3') 
// );
```

Autowiring in a constructor by argument name:

```php
// Class declaration
namespace App;

class MyUsers {
    public function __construct(public array $listOfUsers) {}
}
```

```php
// Definitions for DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make(
    [
        
        'listOfUsers' => // argument name
            ['John', 'Arnold'];
    ]
);
```
```php
// Retrieving data from a container with automatic dependency binding
use App\MyUsers;

/** @var MyUsers::class $users */
$users = $container->get(MyUsers::class);
print implode(',', $users->users); // John, Arnold
```

Autowiring a class by interface
```php
// Class declaration
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
// Definitions for DiContainer
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
// Retrieving data from a container with automatic dependency binding
use App\MyLogger;

/** @var MyClass $myClass */
$myClass = $container->get(MyLogger::class);
$myClass->logger()->debug('...');
```

Another example of getting a class by interface:

```php
// Class Declaration
namespace App;

interface ClassInterface {}

class ClassFirst implements ClassInterface {
    public function __construct(public string $file) {}
}
```

```php
// Definitions for DiContainer
use App\ClassFirst;
use App\ClassInterface;
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();
// ⚠ The "arguments" parameter of the "set" method sets the arguments for the constructor.
$container->set(ClassFirst::class, arguments: ['file' => '/var/log/app.log']);
$container->set(ClassInterface::class, ClassFirst::class);
```

```php
// Retrieving data from a container with automatic dependency binding
use App\ClassInterface;

/** @var ClassFirst $myClass */
$myClass = $container->get(ClassInterface::class);
print $myClass->file; // /var/log/app.log
```

#### DiContainer with PHP attributes

Configuring DiContainer with PHP attributes for definitions.

Getting an existing class and allowing simple parameter types in the constructor:
```php
// Class declaration
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
// Definitions for DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make(
    ['pdo_dsn' => 'sqlite:/opt/databases/mydb.sq3']
);
```

```php
// Retrieving data from a container with automatic dependency binding
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```

Using the Inject attribute on simple (built-in) types for
getting data from a container where the key "users_data" is defined in the container:

```php
// Class declaration
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
// Definitions for DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$definitions = [
    'users_data' => ['user1', 'user2'],
];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Retrieving data from a container with automatic dependency binding
use App\{MyUsers, MyEmployers};

/** @var MyUsers::class $users */
$users = $container->get(MyUsers::class);
print implode(',', $users->users); // user1, user2
/** @var MyEmployers::class $employers */
$employers = $container->get(MyEmployers::class);
print implode(',', $employers->employers); // user1, user2
```

Resolving class via interface:

```php
// Class Declaration
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
// Definitions for DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make([
    'logger_file' => '/var/log/app.log'
]);
```

```php
// Retrieving data from a container with automatic dependency binding
use App\MyLogger;

/** @var MyLogger $myClass */
$myClass = $container->get(MyLogger::class);
print $myClass->customLogger->loggerFile(); // /var/log/app.log
```
#### Access array delimiter notation

Access "container-id" with nested definitions.

The default delimiter character is `.`

An arbitrary delimiter character can be defined in:

* `Kaspi\DiContainer\DiContainer::__construct` argument `$delimiterAccessArrayNotationSymbol`
* `Kaspi\DiContainer\DiContainerFactory::make` argument `$delimiterAccessArrayNotationSymbol`


###### Access-array-delimiter-notation definition based on manual configuration

```php
// Definitions for DiContainer
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
// Class Declaration
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
// Retrieving data from a container with automatic dependency binding
use App\SendEmail;

/** @var SendEmail $myClass */
$sendEmail = $container->get(SendEmail::class);
print $sendEmail->from; // admin@mail.com
print $sendEmail->logger->file; // /var/app.log
```

###### Access-array-delimiter-notation - definitions based on PHP attributes.

```php
// Definitions for DiContainer
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
// Class Declaration
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
// Retrieving data from a container with automatic dependency binding
use App\SendEmail;

/** @var SendEmail $myClass */
$sendEmail = $container->get(SendEmail::class);
print $sendEmail->from; // admin@mail.com
print $sendEmail->logger->file; // /var/app.log
```


## Тесты
Run test without code coverage
```shell
composer test
```
Run test with code coverage
```shell
./vendor/bin/phpunit
```

## Static code analysis

For static analysis we use the package [Phan](https://github.com/phan/phan).

Running without PHP extension [PHP AST](https://github.com/nikic/php-ast)

```shell
./vendor/bin/phan --allow-polyfill-parser
```

## Code style

Code styling by `php-cs-fixer`

```shell
composer fixer
``` 

## Using Docker image with PHP 8.0, 8.1, 8.2, 8.3

You can specify the image with the PHP version in the `.env` file in the `PHP_IMAGE` key.
By default, the container is built with the `php:8.0-cli-alpine` image.

Build container
```shell
docker-compose build
```
Install php dependencies via composer:
```shell
docker-compose run --rm php composer install
```
Run tests with code coverage
```shell
docker-compose run --rm php vendor/bin/phpunit
```
⛑ the results will be in the `.coverage-html` folder

Static code analysis Phan

```shell
docker-compose run --rm php vendor/bin/phan
```

Run shell in docker container:
```shell
docker-compose run --rm php sh
```
