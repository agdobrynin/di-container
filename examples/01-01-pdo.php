<?php

declare(strict_types=1);

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;

use function Kaspi\DiContainer\diAutowire;

require_once './vendor/autoload.php';

class MyUsers
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(private PDO $pdo, private string $usersClass)
    {
        if (!class_exists($this->usersClass)) {
            throw new InvalidArgumentException("{$this->usersClass} does not exist");
        }
    }

    public function init(): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->query('create table users (name string)');
        $this->pdo->query('insert into users values("Ivan"), ("Vasiliy"), ("Piter")');
        $this->pdo->commit();
    }

    public function getAllUsers(): array
    {
        return $this->pdo->query('select * from users')
            ->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $this->usersClass)
        ;
    }
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
    diAutowire(MyUsers::class)
        ->bindArguments(usersClass: User::class),
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
$users = $container->get(MyUsers::class); // $pdo->dsn === 'sqlite::memory:'
$users->init();

\var_dump('👤', $users->getAllUsers());
