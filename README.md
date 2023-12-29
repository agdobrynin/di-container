### DiContainer

Kaspi/container — это легковесный контейнер внедрения зависимостей для PHP >= 8.0 с автоматическим связыванием зависимостей.

#### Установка

Перед использованием установить через composer

```shell
composer require kaspi/di-container
```

#### Примеры использования

Простой пример использования доступен в [репозитории](https://github.com/agdobrynin/di-container-examples) 🦄

---

Получение существующего класса и разрешение простых типов параметров в конструкторе:
```php
// Объявление класса
namespace App;

class MyClass {
    public function __construct(public \PDO $pdo) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$container = DiContainerFactory::make(
    [
        \PDO::class => [
            // в конструкторе класса \PDO
            // аргумент с именем $dsn получит значение
            'arguments' => [
                'dsn' => 'sqlite:/opt/databases/mydb.sq3',
            ],
        ];
    ]
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```

Использование именованного аргумента в объявлении:

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

// Определение символа разделителя параметров для авто связывания
// по умолчанию символ @
// значение-ссылка начинается с символа указанного
// в KeyGeneratorForNamedParameter - например "@data" будет искать
// в контейнере ключ "data".

$definitions = [
    'data' => ['user1', 'user2'],
    App\MyUsers::class => [
        'arguments' => [
            'users' => '@data',
        ],
    ],
    App\MyEmployers::class => [
        'arguments' => [
            'employers' => '@data',
        ],
    ],
];
// по умолчанию символ разделитель @
// указан в параметре "delimiterForNotationParamAndClass" метода 
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
use Psr\Log\LoggerInterface;
use Monolog\{Logger, Handler\StreamHandler, Level};

$container = DiContainerFactory::make()
$container->set('loggerFile', '/path/to/your.log');
$container->set('loggerName', 'app-logger');
$container->set(
    LoggerInterface::class,
    static function (string $loggerFile, string $loggerName) {
        return (new Logger($loggerName))
            ->pushHandler(new StreamHandler($loggerFile));
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
$container->set(ClassFirst::class, ['arguments' => ['file' => '/var/log/app.log']]);
$container->set(ClassInterface::class, ClassFirst::class);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\ClassInterface;

/** @var ClassFirst $myClass */
$myClass = $container->get(ClassInterface::class);
print $myClass->file; // /var/log/app.log
```

##### Тесты
Прогнать тесты без подсчета покрытия кода
```shell
composer test
```
Запуск тестов с проверкой покрытия кода тестами
```shell
./vendor/bin/phpunit
```

##### Code style
Для приведения кода к стандартам используем php-cs-fixer который объявлен 
в dev зависимости composer-а

```shell
composer fixer
``` 

#### Использование Docker образа с PHP 8.0

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
