# DiContainer

Kaspi/di-container — это легковесный контейнер внедрения зависимостей для PHP >= 8.0 с автоматическим связыванием.

#### Установка

```shell
composer require kaspi/di-container
```
#### Особенности

- Поддержка "**zero configuration for dependency definitions**" - когда ненужно объявлять зависимость если класс существуют и может быть запрошен по "PSR-4 auto loading"
- Поддержка **Php-атрибутов** для конфигурирования сервисов в контейнере.

#### Быстрый старт
```php
// определение контейнера с настройкой "zero configuration for dependency definitions"
// когда ненужно объявлять зависимость если класс существуют
// и может быть запрошен по "PSR-4 auto loading"
$container = (new \Kaspi\DiContainer\DiContainerFactory())->make();
```

```php
// определение класса
namespace App\Controllers\Post;

use App\Services\Mail;
use App\Models\Post;

class  Post {
    public function __construct(private Mail $mail, private Post $post){}
    
    public function send(): bool {
        $this->mail->subject('Publication success')->body('Post <'.$post->title.'> was published.');
    }
}
```
```php
// получить класс Post с внедренными сервисами Mail, Post и выполнить метод "send"
$container->get(App\Controllers\Post::class)->send();
```

* Примеры использования пакета kaspi/di-container в [репозитории](https://github.com/agdobrynin/di-container-examples) 🦄

### Конфигурирование DiContainer

Для конфигурирования параметров используется класс:
`Kaspi\DiContainer\DiContainerConfig::class` который имплементирует интерфейс `Kaspi\DiContainer\Interfaces\DiContainerConfigInterface`

```php
$diConfig = new \Kaspi\DiContainer\DiContainerConfig(
    // Использовать автоматическое разрешение аргументов
    // сервисов-классов или методов-классов или функций.
    useAutowire: true,
    // Ненужно объявлять каждую зависимость.
    // Если класс или функция или интерфейс существуют -
    // то он может быть запрошен по "PSR-4 autoloading".
    useZeroConfigurationDefinition: true,
    // Использовать Php-атрибуты для объявления зависимостей.
    useAttribute: true,
    // Сервис (объект) будет создаваться заново при разрешении зависимости
    // если знание true, то объект будет создан как Singleton.
    isSingletonServiceDefault: false,
    // Строка (символ) определяющий шаблон как ссылку другой контейнер
    referenceContainerSymbol: '@',
);
// передать настройки в контейнер
$container = new \Kaspi\DiContainer\DiContainer(config: $diConfig);
```
Или использовать фабрику с настроенными по умолчанию параметрами:
```php
$container = (new \Kaspi\DiContainer\DiContainerFactory())->make(definitions: []);
```

### Примеры использования

------------------------------------
* Примеры использования [DiContainer с конфигурированием на основе php-определений](#DiContainer-с-конфигурированием-на-основе-php-определений).
* Примеры использования [DiContainer c конфигурированием через PHP атрибуты](#DiContainer-c-конфигурированием-через-PHP-атрибуты).

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

#### Внедрение значений зависимостей аргументов по контейнер-id в определениях.

Для внедрения зависимостей в аргуемнты испольузется синтаксис `@container-id` - 
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

#### 🔑 DiContainer c конфигурированием через PHP атрибуты

[В конфигурации контейнера](#конфигурирование-dicontainer) по умолчанию параметр `useAttribute` включён.

Доступные атрибуты:
- **Inject** - внедрение зависимости в аргументы конструктор или методы класса.
- **Service** - определение для интерфейса какой класс будет вызван и разрешен в контейнере.
- **DiFactory** - Фабрика для разрешения зависимостей. Класс должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`

##### Inject

```php
#[\Kaspi\DiContainer\Attributes\Inject(
    id: '', // определение зависимости
    arguments: [], // аргументы конструктора для зависимости
    isSingleton: false,  // сервис создаётся как Singleton
)]
```

Получение существующего класса и разрешение простых типов параметров в конструкторе:

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

Использование **Inject** атрибута на простых (встроенных) типах для получения данных из контейнера:

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

Внедрение типизированных аргументов через атрибут **Inject**:

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

##### Service

```php
#[\Kaspi\DiContainer\Attributes\Service(
    id: '', // Класс реализующий интерфейс
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

##### DiFactory

```php
#[\Kaspi\DiContainer\Attributes\Service(
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

### DiContainer::call

Контейнер предоставляет метод call(), который может вызывать любой callable PHP-метод.

🔥 - будет добавлено описание - 🔥 

## Тесты
Прогнать тесты без подсчёта покрытия кода
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
