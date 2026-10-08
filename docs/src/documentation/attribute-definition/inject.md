# Inject

## Обзор { #overview }

Применяется к параметрам конструктора класса, метода или функции.

```php
#[Inject(string $id = '')]
```
Аргумент:
- `$id` - определение зависимости (класс, интерфейс, идентификатор контейнера).

> [!NOTE]
> При пустом значении в `\Kaspi\DiContainer\Attributes\Inject::$id` контейнер попытается получить
> значение исходя из типа параметра (_type hint_).

> [!WARNING]
> При разрешении зависимости для составного типа (_union, intersection types_)
> может быть выброшено исключение, [для исправления этой ошибки
> необходима конкретизация типа](#разрешение-зависимости-объединенного-типа-через-атрибут-inject).


### Атрибут `Inject` для получения по идентификатору контейнера в конструкторе:

```php
// src/Databases/MyDb.php
namespace App\Databases;

use Kaspi\DiContainer\Attributes\Inject;

class MyDb {

    public function __construct(
        #[Inject('services.pdo-env')]
        public \PDO $pdo
    ) {}
}
```
```php
// file config/params.php
return [
    'db_dsn.prod' => 'sqlite:/data/prod/db.db',
    'db_dsn.local' => 'sqlite:/tmp/db.db',
    'db_dsn.test' => 'sqlite::memory:',
];
```
```php
// file config/main.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\{diAutowire, diCallable, diParameter};

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {
    // 🚩 загрузка конфигураций параметров контейнера.
    $configurator->loadParameters(__DIR__.'/params.php');

    yield 'services.pdo-prod' => diAutowire(PDO::class)
        ->bindArguments(dsn: diParameter('db_dsn.prod'));

    yield 'services.pdo-local' => diAutowire(PDO::class)
        ->bindArguments(dsn: diParameter('db_dsn.local'));

    yield 'services.pdo-test' => diAutowire(PDO::class)
        ->bindArguments(dsn: diParameter('db_dsn.test'));

    yield 'services.pdo-env' => diCallable(
        definition: static fn (ContainerInterface $container) => match (\getenv('APP_PDO')) {
            'prod' => $container->get('services.pdo-prod'),
            'test' => $container->get('services.pdo-test'),
            default => $container->get('services.pdo-local')
        },
        isSingleton: true,
    );
};
```
```php
// определение контейнера.
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load('config/main.php')
    ->build()
;

\putenv('APP_PDO=local');

// PDO будет указывать на базу sqlite:/tmp/db.db'
$myClass = $container->get(App\Databases\MyDb::class);
```

### Атрибут `Inject` для разрешения параметров переменной длины

Атрибут имеет признак `repetable`

> [!WARNING]
> Параметр переменной длины является опциональным и если у него не задан
> PHP атрибут указывающий какой аргумент использовать
> для разрешения зависимости, то он будет пропущен.


```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {}
```
```php
// src/Rules/RuleB.php
namespace App\Rules;

class RuleB implements RuleInterface {}
```
```php
// src/Rules/RuleGenerator.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Inject;

class RuleGenerator {

    private iterable $rules;

    public function __construct(
        #[Inject(RuleB::class)]
        #[Inject(RuleA::class)]
        RuleInterface ...$inputRule
    ) {
        $this->rules = $inputRule;
    }
    
    public function getRules(): array {
        return $this->rules;
    }
}
```
```php
// определения для контейнера
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

### Атрибут `Inject` для параметра переменной длины по идентификатору контейнера

> [!WARNING]
> Параметр переменной длины является опциональным и если у него не задан
> PHP атрибут указывающий какой аргумент использовать
> для разрешения зависимости, то он будет пропущен.

```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {}
```
```php
// src/Rules/RuleB.php
namespace App\Rules;

class RuleB implements RuleInterface {}
```
```php
// src/Rules/RuleGenerator.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Inject;

class RuleGenerator {
    private iterable $rules;

    public function __construct(
        #[Inject('services.rules.b')]
        #[Inject('services.rules.a')]
        RuleInterface ...$inputRule
    ) {
        $this->rules = $inputRule;
    }
    
    public function getRules(): array {
        return $this->rules;
    }
}
```
```php
// config/services/php
use Kaspi\DiContainer\{diAutowire, diCallable};

return static function (): \Generator {
    yield 'services.rules.a' => diCallable(
        // Автоматически внедрит зависимости этой callback функции
        static function (App\Rules\RuleA $a) {
            // тут возможны дополнительные настройки объекта
            return $a
        }
    ),

    yield 'services.rules.b' => diAutowire(App\Rules\RuleB::class),
};
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

### Атрибут `Inject` при внедрении класса для интерфейса.
```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {}
```
```php
// src/Rules/RuleGenerator.php
namespace App\Rules;

class RuleGenerator {

    public function __construct(
        #[Inject(RuleA::class)]
        public RuleInterface $inputRule
    ) {}

}
```
```php
// определения для контейнера
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->inputRule instanceof App\Rules\RuleA); // true
```
