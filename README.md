### DiContainer

Kaspi/container — это легковесный контейнер внедрения зависимостей для PHP >= 8.0 с автоматическим связыванием зависимостей.

#### Установка

Перед использованием установить через composer

```shell
composer require kaspi/di-container
```

#### Примеры использования
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
use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\KeyGeneratorForNamedParameter;

$keyGen = new KeyGeneratorForNamedParameter();
$autowired = new Autowired($keyGen);
$container = new DiContainer(
    config: [
        \PDO::class => [
            // в конструкторе класса \PDO
            // аргумент с именем $dsn получит значение
            'dsn' => 'sqlite:/opt/databases/mydb.sq3'
        ];
    ],
    autowire: $autowired
);
```
```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```

Получение класса по интерфейсу
```php
// Объявление класса
namespace App;

use Psr\Log\LoggerInterface;

class MyClass {
    protected LoggerInterface $logger;
    
    public function __construct(public LoggerInterface $logger) {
        $this->logger = $this->logger;
    }
    
    public function logger(): LoggerInterface {
        return $this->logger;
    }
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\KeyGeneratorForNamedParameter;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$keyGen = new KeyGeneratorForNamedParameter();
$autowired = new Autowired($keyGen);
$container = new DiContainer(autowire: $autowired);

$container->set(
    LoggerInterface::class,
    static fn () => (new Logger('my-logger'))
            ->pushHandler(new StreamHandler('/path/to/your.log', \Monolog\Level::Warning));
    }
);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use App\MyClass;

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->logger()->debug('...');
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
