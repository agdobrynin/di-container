### DiContainer

Kaspi/container — это легковесный контейнер внедрения зависимостей для PHP >= 8.0 с автоматическим связыванием зависимостей.

#### Установка

Перед использованием установить через composer

```shell
composer require kaspi/di-container
```

#### Простой пример использования
Получение существующего класса и разрешение простых типов параметров в конструкторе:
```php
class MyClass {
    public function __construct(public \PDO $pdo) {}
}

// ...

$autowired = new \Kaspi\DiContainer\Autowired();
$container = new \Kaspi\DiContainer\DiContainer(
    config: [
        \PDO::class => [
            // в конструкторе класса \PDO
            // аргумент с именем $dsn получит значение
            'dsn' => 'sqlite:/opt/databases/mydb.sq3'
        ];
    ],
    autowire: $autowired
);

//...

/** @var MyClass $myClass */
$myClass = $container->get(MyClass::class);
$myClass->pdo->query('...')
```
Получение класса по интерфейсу
```php
class MyClass {
    protected \Psr\Log\LoggerInterface $logger;
    
    public function __construct(public \Psr\Log\LoggerInterface $logger) {
        $this->logger = $this->logger;
    }
    
    public function logger(): \Psr\Log\LoggerInterface {
        return $this->logger;
    }
}

// ...

$autowired = new \Kaspi\DiContainer\Autowired();
$container = new \Kaspi\DiContainer\DiContainer(
    config: [
        \Psr\Log\LoggerInterface::class => static function () {
            return (new \Monolog\Logger('my-logger'))
                ->pushHandler(
                    new Monolog\Handler\StreamHandler('/path/to/your.log', \Monolog\Level::Warning)
                );
        }
    ],
    autowire: $autowired
);

// ...

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
