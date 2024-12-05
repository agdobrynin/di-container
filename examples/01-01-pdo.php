<?php

declare(strict_types=1);

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;

use function Kaspi\DiContainer\diAutowire;

require_once './vendor/autoload.php';

class MyClass
{
    public function __construct(public PDO $pdo) {}
}

class User
{
    private string $name;

    public function sayHello(): string
    {
        return "Hello! My name is {$this->name}";
    }
}

$definitions = [
    // класс PDO создать единожды и всегда возвращать тот же объект
    diAutowire(PDO::class, isSingleton: true)
        // с аргументом $dsn в конструкторе.
        ->bindArguments(dsn: 'sqlite::memory:'),
];

$config = new DiContainerConfig(
    useZeroConfigurationDefinition: true,
    useAttribute: false,
    isSingletonServiceDefault: false,
);

$container = new DiContainer(definitions: $definitions, config: $config);

// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(MyClass::class); // $pdo->dsn === 'sqlite::memory:'
$myClass->pdo->query('create table users (name string)');
$myClass->pdo->query('insert into users values("Ivan"), ("Vasiliy"), ("Piter")');
$users = $myClass->pdo->query('select * from users')
    ->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User')
;
\var_dump('👤', $users);
// получать один и тот же объект PDO::class
// так как в определении указан isSingleton=true
$myClassTwo = $container->get(MyClass::class);

\var_dump(
    ' 🐎 ',
    \spl_object_id($myClass->pdo) === \spl_object_id($myClassTwo->pdo)
); // true
