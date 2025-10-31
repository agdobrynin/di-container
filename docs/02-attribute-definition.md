# #️⃣ DiContainer c конфигурированием через PHP атрибуты

[В конфигурации контейнера](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#конфигурирование_dicontainer) по умолчанию параметр `useAttribute` включён.

При конфигурировании контейнера можно совмещать php-атрибуты и php-определения.

> [!WARNING]
> При разрешении зависимостей в контейнере (_получение результата_) более высокой
> приоритет имеют php-атрибуты чем php-определения.
> 
> Если класс или интерфейс конфигурируется через php атрибуты
> и одновременном через файлы конфигурации, то при одинаковых идентификаторах
> контейнера будет выброшено исключение.
> Необходимо выбрать только один способ конфигурации сервиса или через php атрибуты или через файлы-определения.

Доступные атрибуты:
- **[Autowire](#autowire)** – конфигурирование PHP класса как сервиса или их набора в контейнере.
- **[AutowireExclude](#autowireexclude)** – запретить разрешение PHP класса или интерфейса в контейнере.
- **[Setup](#setup)** - вызов метода PHP класса для настройки сервиса без учёта возвращаемого значения, _mutable setter method_.
- **[SetupImmutable](#setupimmutable)** - вызов метода PHP класса для настройки сервиса с учёта возвращаемого значения, _immutable setter method_.
- **[Inject](#inject)** – внедрение зависимости в параметры конструктора PHP класса, метода.
- **[InjectByCallable](#injectbycallable)** – внедрение зависимости в параметры конструктора PHP класса, метода через `callable` тип.
- **[Service](#service)** – определение для интерфейса какой PHP класс будет вызван и разрешен в контейнере.
- **[DiFactory](#difactory)** – фабрика для c помощью которой разрешается зависимость класса. Класс должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`
- **[ProxyClosure](#proxyclosure)** – внедрение зависимости в параметры конструктора PHP класса, метода или аргументов функции с отложенной инициализацией через класс `\Closure`, анонимную функцию.
- **[Tag](#tag)** – определение тегов для класса.
- **[TaggedAs](#taggedas)** – внедрение тегированных определений в параметры конструктора, метода PHP класса.

## Autowire
Применятся к классу для конфигурирования сервиса в контейнере.

```php
#[Autowire(string $id = '', ?bool $isSingleton = null)]
```
Аргументы:
- `$id` – идентификатор контейнера для класса (_container identifier_).
- `$isSingleton` – зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](https://github.com/agdobrynin/di-container/tree/main?tab=readme-ov-file#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

> [!NOTE]
> Пустая строка в аргументе `$id` будет представлена как полное имя класса
> с учётом пространства имён – **fully qualified class name** и будет являться идентификатором контейнера для этого класса.

> [!WARNING]
> Чтобы была возможность использовать аргумент `$id` отличающийся от полного имени класса (_fully qualified class name_)
> необходимо использовать [DefinitionsLoader](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md).

> [!TIP]
> Атрибут `#[Autowire]` имеет признак `repetable` и может быть
> применен несколько раз для класса. Аргумент `$id`
> у каждого атрибута должен быть уникальным, иначе будет выброшено
> исключение при разрешении класса контейнером.

```php
// src/Services/SomeService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isSingleton: true)] // $id будет присвоен 'App\Services\SomeService'
#[Autowire(id: 'services.some_service')]
class SomeService {}
```
Используем [DefinitionsLoader](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md)
для конфигурирования контейнера так как в одном из атрибутов `#[Autowire]` для
аргумента `$id` используется значение отличное от полного имени класса (_fully qualified class name_):
```php
use Kaspi\DiContainer\DiContainerFactory;
use App\Services\SomeService;

// Загрузить определения путем сканирования файловой системы
$loader = (new DefinitionsLoader())
    ->import(namespace: 'App\\', src: __DIR__.'/src/');

// по умолчанию сервисы создаются каждый раз заново
$container = (new DiContainerFactory())->make(
    $loader->definitions()
);

var_dump($container->has(SomeService::class)); // true

// получить сервис по идентификатору контейнера `App\Services\SomeService`
// сконфигурированным через атрибут #[Autowire]
$service = $container->get(SomeService::class);

var_dump(
    \spl_object_id($service) === \spl_object_id($container->get(SomeService::class))
); // true

var_dump($container->has('services.some_service')); // true

// получить сервис по идентификатору контейнера `services.some_service`
// сконфигурированным через атрибут #[Autowire]
$serviceSome = $container->get('services.some_service');

var_dump(
    \spl_object_id($serviceSome) === \spl_object_id($container->get('services.some_service')))
); // false
```
> [!NOTE]
> При получении сервиса через идентификатор `App\Services\SomeService::class` сервис
> создаётся единожды так как у атрибута конфигурирующего этот сервис
> аргумент `isSingleton` указан как `true`.
> 
> При получении сервиса через идентификатор `services.some_service` сервис
> создается каждый раз заново так как у атрибута конфигурирующего этот сервис
> аргумент `isSingleton` не указан и по умолчанию имеет значение `null` 
>, но в конфигурации контейнера по умолчанию установлено создавать сервисы
> каждый раз заново.


## AutowireExclude
Применятся к классу или интерфейсу для исключения разрешения зависимости контейнером.

```php
#[AutowireExclude]
```
У атрибута нет аргументов.

> [!WARNING]
> Если `#[AutowireExclude]` применен к классу или интерфейсу то
> любые другие атрибуты будут игнорированы.

```php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;use Kaspi\DiContainer\Attributes\AutowireExclude;

#[Autowire(isSingleton: true)]
#[AutowireExclude]
class SomeService {}
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use App\Services\SomeService;

$container = (new DiContainerFactory())->make();

var_dump($container->has(SomeService::class)); // false
```
> [!NOTE]
> Так как класс `App\Services\SomeService::class` сконфигурирован атрибутом `AutowireExclude`
> то атрибут `Autowire` указанный для класса будет проигнорирован. 

## Setup

Применяется к методам PHP класса для настройки сервиса без учёта возвращаемого значения, _mutable setter method_.

```php
#[Setup(mixed ...$argument)]
```

Аргументы:
- `$argument` - аргументы для передачи в вызываемый метод.

Значениями для `$argument` разрешается указывать скалярные типы данных,
массивы (array), специальный тип null и начиная с **PHP 8.1.0** объекты,
которые создают синтаксисом `new ClassName()`.

> [!TIP]
> Для неустановленных аргументов в методе через `$argument` контейнер по попытается разрешить зависимости автоматически.

> [!TIP]
> Сеттер метод через PHP атрибут `#[Setup]` можно применять несколько раз, контейнер
> вызовет сеттер метод указанное количество раз.

Пример добавления зависимостей через сеттер метод: 
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

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use App\Rules\{RuleA, RuleB};

class RuleGenerator {

    private iterable $rules = [];
    
    // Аргумент передается как объект для PHP 8.1.0 и выше.
    #[Setup(inputRule: new DiDefinitionGet(RuleB::class))]
    #[Setup(inputRule: new DiDefinitionGet(RuleA::class))]
    // ⚠ для PHP 8.0.x аргумент передается как специальная строка
    // и будет интерпретирована как идентификатор контейнера.
    // #[Setup(inputRule: '@'.RuleB::class)] 
    // #[Setup(inputRule: '@'.RuleA::class)]
    public function addRule(RuleInterface $inputRule): void {
        $this->rules[] = $inputRule;
    }
    
    public function getRules(): array {
        return $this->rules;
    }
}
```
```php
// определения для контейнера
use Kaspi\DiContainer\{DefinitionsLoader, DiContainerFactory};

$loader = (new DefinitionsLoader())
    ->import(namespace: 'App\\', src: __DIR__.'/src/');

$container = (new DiContainerFactory())
    ->make(
        $loader->definitions()
    );

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

> [!WARNING]
> Для случаев когда нельзя передать `$argument` как объект (_для PHP ниже версии 8.1.0_)
> существует возможность передать строковое значение указывающее
> контейнеру на необходимость вызвать другой сервис по идентификатору контейнера —
> строка должна начинаться с символа `@`:
> - `@container-identifier` — получить зависимость из контейнера по идентификатору контейнера `container-identifier`
> 
> Если нужно передать значение строкового аргумента начинающегося с `@`,
> то необходимо экранировать его добавив ещё один символ `@` в начало строки,
> чтобы контейнер не считал значение идентификатором контейнера:
> - `@@some-string-value` — будет интерпритирована как строка `@some-string-value`
> 
> Если в `$argument` передано строковое значение не начинающаяся со специального символа `@`,
> то она будет передана в метод как есть.

## SetupImmutable

Применяется к методам PHP класса для настройки сервиса с учётом, 
что вызванный сеттер метод возвращает новый объект (_immutable setter method_).
Возвращаемое значение метода должно быть `self`, `static`
или того же класса, что и сам сервис.

```php
#[SetupImmutable(mixed ...$argument)]
```

Аргументы:
- `$argument` - аргументы для передачи в вызываемый метод.

Значениями для `$argument` разрешается указывать скалярные типы данных,
массивы (array), специальный тип null и начиная с **PHP 8.1.0** объекты,
которые создают синтаксисом `new ClassName()`.

> [!TIP]
> Для неустановленных аргументов в методе через `$argument` контейнер по попытается разрешить зависимости автоматически.

> [!TIP]
> Сеттер метод через PHP атрибут `#[SetupImmutable]` можно применять несколько раз, контейнер
> вызовет сеттер метод указанное количество раз.

Пример добавления зависимостей через сеттер метод который возвращает новый объект:
```php
// src/App/Loggers/MyLogger.php
namespace App\Services;

use Psr\Log\LoggerInterface;

class MyLogger implements LoggerInterface
{
    // implement all methods from interface
}
```
```php
// src/App/Services/MyService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use App\Loggers\MyLogger;
use Psr\Log\LoggerInterface;

class MyService
{
    private ?LoggerInterface $logger;

    // Аргумент передается как объект для PHP 8.1.0 и выше.    
    #[SetupImmutable(logger: new DiDefinitionGet(MyLogger::class))]
    // ⚠ для PHP 8.0.x аргумент передается как специальная строка
    // и будет интерпретирована как идентификатор контейнера.
    // #[SetupImmutable(logger: '@'.App\Loggers\MyLogger::class)]
    public function withLogger(?LoggerInterface $logger): static
    {
        $new = clone $this;
        $new->logger = $logger;
        
        return $new;    
    }
    
    public function getLogger():?LoggerInterface
    {
        return $this->logger;
    }
}
```
```php
// определения для контейнера
use Kaspi\DiContainer\{DefinitionsLoader, DiContainerFactory};

$loader = (new DefinitionsLoader())
    ->import(namespace: 'App\\', src: __DIR__.'/src/');

$container = (new DiContainerFactory())
    ->make(
        $loader->definitions()
    );

$myService = $container->get(App\Services\MyService::class);

var_dump($myService->getLogger() instanceof Psr\Log\LoggerInterface); // true
```

> [!WARNING]
> Для случаев когда нельзя передать `$argument` как объект (_для PHP ниже версии 8.1.0_)
> существует возможность передать строковое значение указывающее
> контейнеру на необходимость вызвать другой сервис по идентификатору контейнера —
> строка должна начинаться с символа `@`:
> - `@container-identifier` — получить зависимость из контейнера по идентификатору контейнера `container-identifier`
>
> Если нужно передать значение строкового аргумента начинающегося с `@`,
> то необходимо экранировать его добавив ещё один символ `@` в начало строки,
> чтобы контейнер не считал значение идентификатором контейнера:
> - `@@some-string-value` — будет интерпритирована как строка `@some-string-value`
>
> Если в `$argument` передано строковое значение не начинающаяся со специального символа `@`,
> то она будет передана в метод как есть.

## Inject

Применяется к аргументам конструктора класса, метода или функции.

```php
#[Inject(string $id = '')]
```
Аргумент:
- `$id` - определение зависимости (класс, интерфейс, идентификатор контейнера).

> [!NOTE]
> При пустом значении в `$id` контейнер попытается получить
> результат исходя из типа аргумента.
> Если не удалось получить `$id` по типу аргумента
> то будет выполнена попытка разрешить зависимость
> по имени аргумента, используя имя аргумента как идентификатор контейнера.

> [!WARNING]
> При разрешении зависимости для объединенного типа (_union type_)
> может быть выброшено исключение, [для исправления этой ошибки
> необходима конкретизация типа](#разрешение-зависимости-объединенного-типа-через-inject).


### Атрибут #[Inject] для получения по идентификатору контейнера в конструкторе:

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
// file config/main.php
use function Kaspi\DiContainer\{diAutowire, diCallable};

return [
    'services.pdo-prod' => diAutowire(PDO::class)
        ->bindArguments(dsn: 'sqlite:/data/prod/db.db'),

    'services.pdo-local' => diAutowire(PDO::class)
        ->bindArguments(dsn: 'sqlite:/tmp/db.db'),

    'services.pdo-test' => diAutowire(PDO::class)
        ->bindArguments(dsn: 'sqlite::memory:'),

    'services.pdo-env' => diCallable(
        definition: static fn (ContainerInterface $container) => match (\getenv('APP_PDO')) {
            'prod' => $container->get('services.pdo-prod'),
            'test' => $container->get('services.pdo-test'),
            default => $container->get('services.pdo-local')
        },
        isSingleton: true,
    ),
];
```
```php
// определение контейнера.
$container = (new DiContainerFactory())->make(require 'config/main.php');

\putenv('APP_PDO=local');

// PDO будет указывать на базу sqlite:/tmp/db.db'
$myClass = $container->get(App\Databases\MyDb::class);
```

### Атрибут #[Inject] для разрешения параметров переменной длины

Атрибут имеет признак `repetable`

> [!WARNING]
> Параметр переменной длинны является опциональным и если у него не задан
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
use Kaspi\DiContainer\{DefinitionsLoader, DiContainerFactory};

$loader = (new DefinitionsLoader())
    ->import(namespace: 'App\\', src: __DIR__.'/src/');

$container = (new DiContainerFactory())
    ->make(
        $loader->definitions()
    );

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

> [!TIP]
> `DefinitionsLoader` – [загрузчик определений в контейнер из директорий](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md). 

### Атрибут #[Inject] для параметра переменной длины по идентификатору контейнера

> [!WARNING]
> Параметр переменной длинны является опциональным и если у него не задан
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
use Kaspi\DiContainer\{DefinitionsLoader, DiContainerFactory};

$loader = (new DefinitionsLoader())
    ->load(__DIR__.'/config/services.php');

$container = (new DiContainerFactory())
    ->make(
        $loader->definitions()
    );

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```
> [!TIP]
> `DefinitionsLoader` загрузчик определений в контейнер
> через [конфигурационные файлы](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md#%D0%B7%D0%B0%D0%B3%D1%80%D1%83%D0%B7%D0%BA%D0%B0-%D0%B8%D0%B7-%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B0%D1%86%D0%B8%D0%BE%D0%BD%D0%BD%D1%8B%D1%85-%D1%84%D0%B0%D0%B9%D0%BB%D0%BE%D0%B2)
> и [импорт и настройку сервисов из директорий](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md#%D0%B8%D0%BC%D0%BF%D0%BE%D1%80%D1%82-%D0%BA%D0%BB%D0%B0%D1%81%D1%81%D0%BE%D0%B2-%D0%B8%D0%B7-%D0%B4%D0%B8%D1%80%D0%B5%D0%BA%D1%82%D0%BE%D1%80%D0%B8%D0%B9).

### Атрибут #[Inject] и класс реализующий DiFactoryInterface
Класс реализующий `Kaspi\DiContainer\Interfaces\DiFactoryInterface` будет вызван контейнером и исполнен метод `__invoke`
который является результатом для Inject атрибута.
```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {
    // ...
    public function doConfig(array $config): void {
        // configure rule here
    }
}
```
```php
// src/Factories/RuleAFactory.php
namespace App\Factories;

use App\Rules\RuleA;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

class RuleAFactory implements DiFactoryInterface {

    public function __construct(
        private RuleA $ruleA,
    ) {}

    public function __invoke(ContainerInterface $container): RuleA {
        // тут возможны дополнительные настройки объекта ruleA
        $this->ruleA->doConfig(['key' => 'abc']);

        return $this->ruleA;
    }

}
```
```php
// src/Rules/RuleGenerator.php
namespace App\Rules;

use App\Factories\RulesDiFactory;
use App\Rules\RuleInterface;

class RuleGenerator {

    public function __construct(
        #[Inject(RulesDiFactory::class)]
        private RuleInterface $rule;
    ) {}
    
    public function getRule(): RuleInterface {
        return $this->rule;
    }

}
```
```php
// определения для контейнера
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRule() instanceof App\Rules\RuleA); // true
```

### Атрибут **#[Inject]** при внедрении класса для интерфейса.
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
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->inputRule instanceof App\Rules\RuleA); // true
```

## InjectByCallable

Применяется к параметрам конструктора класса, метода или функции через [`callable` тип](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md#поддерживаемые-типы)
на основе [вызова `DiContainer::call()`](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md).

```php
#[InjectByCallable(string $callable)]
```
Аргументы:
- `$callable` - строка которая может быть преобразована к `callable` для получения результата внедрения.

> [!TIP]
> Аргументы указанные в `callable` вызове могут быть разрешены
> контейнером автоматически.

Пример использования:
```php
// src/Classes/One.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Inject;

class One {
    
    public function __construct(ptivate string $code) {}
    
    public static function config(
        #[Inject('config.secure_code')]
        string $configCode
    ): One {
        return new self($configCode);
    }

}
```
```php
// src/Services/ServiceOne.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\InjectByCallable;

class ServiceOne {

    public function __construct(
        #[InjectByCallable('App\Classes\One::config')]
        private One $one
    ) {}

}
```
```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;

$definitions = [
    'config.secure_code' => 'abc',
];

$container = (new DiContainerFactory())->make($definitions);

// Получение данных из контейнера
$service = $container->get(App\Services\ServiceOne::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Services\ServiceOne::class` в свойстве
> `App\Services\ServiceOne::$one` будет класс `App\Classes\One`
> у которого в свойстве `App\Classes\One::$code` строка `'abc'`
> полученная при создании класса в статическом методе `App\Classes\One::config()`.

> [!TIP]
> Объявить строку для аргумента `$callable` у php атрибута `#[InjectByCallable]`
> можно используя безопасное объявление через магическую константу
> `::class`:
> ```php
>   namespace App\Services;
> 
>   use Kaspi\DiContainer\Attributes\InjectByCallable;
>   use App\Classes\One;
> 
>   class ServiceOne {
>
>       public function __construct(
>            #[InjectByCallable(One::class.'::config')]
>           private One $one
>       ) {}
> 
>   }
> ```

## Service

Применяется к интерфейсу для конфигурирования реализации php интерфейса.
```php
#[Service(string $id, ?bool $isSingleton = null)]
```
Аргументы:
- `$id` - класс реализующий интерфейс (FQCN) или идентификатор контейнера.
- `$isSingleton` - зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](https://github.com/agdobrynin/di-container/tree/main?tab=readme-ov-file#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

> [!NOTE]
> **FQCN** – Fully Qualified Class Name. 

```php
// src/Loggers/CustomLoggerInterface.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Service;

#[Service(CustomLogger::class)] // класс реализующий данный интерфейс.
interface CustomLoggerInterface {
    public function loggerFile(): string;
}
```
```php
// src/Loggers/CustomLogger.php
namespace App\Loggers;

class CustomLogger implements CustomLoggerInterface {

    public function __construct(
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}
```
```php
// src/Loggers/MyLogger.php
namespace App\Loggers;

class MyLogger {

    public function __construct(
        // Контейнер найдёт интерфейс
        // и проверит у него php-атрибут Service.
        public CustomLoggerInterface $customLogger
    ) {}
}
```

```php
// config/services.php
use function Kaspi\DiContainer\diAutowire;

return static function (): \Generator {
    yield diAutowire(App\Loggers\CustomLogger::class)
        // 🌞 подставить в параметр $file в конструкторе.
        ->bindArguments(file: '/var/log/app.log');
};
```

```php
use Kaspi\DiContainer\{DefinitionsLoader, DiContainerFactory};

$loader = (new DefinitionsLoader())
    ->load(__DIR__.'/config/services.php')
    ->import(namespace: 'App\\', src: __DIR__.'/src/');

$container = (new DiContainerFactory())
    ->make(
        $loader->definitions()
    );    

// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\Loggers\MyLogger::class);

print $myClass->customLogger->loggerFile(); // /var/log/app.log
```
> [!TIP]
> `DefinitionsLoader` загрузчик определений в контейнер
> через [конфигурационные файлы](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md#%D0%B7%D0%B0%D0%B3%D1%80%D1%83%D0%B7%D0%BA%D0%B0-%D0%B8%D0%B7-%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B0%D1%86%D0%B8%D0%BE%D0%BD%D0%BD%D1%8B%D1%85-%D1%84%D0%B0%D0%B9%D0%BB%D0%BE%D0%B2)
> и [импорт и настройку сервисов из директорий](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md#%D0%B8%D0%BC%D0%BF%D0%BE%D1%80%D1%82-%D0%BA%D0%BB%D0%B0%D1%81%D1%81%D0%BE%D0%B2-%D0%B8%D0%B7-%D0%B4%D0%B8%D1%80%D0%B5%D0%BA%D1%82%D0%BE%D1%80%D0%B8%D0%B9).

Так же атрибут **Service** можно использовать со ссылкой на другой идентификатор контейнера.

```php
// src/Loggers/CustomLoggerInterface.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Service;

#[Service('services.app_logger')]
interface CustomLoggerInterface {
    public function loggerFile(): string;
}
```
```php
// config/services.php
use App\Loggers\CustomLogger;

return static function (): \Generator {

    yield 'services.app_logger' => static function(): CustomLogger {
        return new CustomLogger(file: '/var/log/app.log');
    }

};
```

## DiFactory
Применятся к классу для разрешения зависимости через вызов класса реализующего `Kaspi\DiContainer\Interfaces\DiFactoryInterface`.

```php
#[DiFactory(string $id, ?bool $isSingleton = null)]
```
Аргументы:
- `$id` - класс (_FQCN_) реализующий интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`.
- `$isSingleton` - зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](https://github.com/agdobrynin/di-container/tree/main?tab=readme-ov-file#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

```php
// src/Classes/SuperClass.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\DiFactory;

// Разрешить зависимость через фабрику и указать контейнеру что это будет Singleton.
#[DiFactory(App\Factory\FactorySuperClass::class, isSingleton: true)]
class SuperClass
{
    public function __construct(public string $name, public int $age) {}
}
```

```php
// src/Factory/FactorySuperClass.php
namespace App\Factory;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class FactorySuperClass implements DiFactoryInterface
{

    public function __invoke(ContainerInterface $container): App\SuperClass
    {
        return new App\Classes\SuperClass('Piter', 22);
    }

}
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();

$myClass = $container->get(App\Classes\SuperClass::class);

print $myClass->name; // Piter
print $myClass->age; // 22
```

## ProxyClosure

Реализация ленивой инициализации параметров класса (зависимости) через функцию обратного вызова.
Применяется к параметрам конструктора класса, метода или функции.

```php
#[ProxyClosure(string $id)]
```
Аргументы:
- `$id` - класс (_FQCN_) реализующий сервис который необходимо разрешить отложено.

Такое объявление сервиса пригодится для «тяжёлых» зависимостей, требующих длительного времени инициализации или ресурсоёмких вычислений.

> [!TIP]
> Подробное объяснение использования [ProxyClosure](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#diproxyclosure)

Пример для отложенной инициализации сервиса через атрибут `#[ProxyClosure]`:

```php
// src/Services/HeavyDependency.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\ProxyClosure;

/**
 * Класс с «тяжёлыми» зависимостями,
 * много ресурсов на инициализацию.
 */
class HeavyDependency {
    public function __construct(...) {}
    public function doMake() {}
}
```
```php
// src/Classes/ClassWithHeavyDependency.php
namespace App\Classes;

use App\Services\HeavyDependency;
use Kaspi\DiContainer\Attributes\ProxyClosure;

class ClassWithHeavyDependency {
    /**
     * 🚩 Подсказка для IDE при авто-дополении (autocomplete).
     * @param Closure(): HeavyDependency $heavyDependency
     */
    public function __construct(
        #[ProxyClosure(HeavyDependency::class)]
        private \Closure $heavyDependency,
        private LiteDependency $liteDependency,
    ) {}
    
    public function doHeavyDependency() {
        ($this->heavyDependency)()->doMake();
    }
    
    public function doLiteDependency() {
        $this->liteDependency->doMakeLite();
    }
}
```
> [!TIP]
> Для подсказок IDE autocomplete используйте
> PhpDocBlock над конструктором: 
> `@param Closure(): HeavyDependency $heavyDependency`

```php
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();

$classWithHeavyDependency = $container->get(App\Classes\ClassWithHeavyDependency::class);

$classWithHeavyDependency->doHeavyDependency();
```
> [!NOTE]
> При разрешении зависимости контейнера `App\Classes\ClassWithHeavyDependency::class`
> свойство в классе `ClassWithHeavyDependency::$heavyDependency` ещё не инициализировано.
> Инициализация произойдёт (_разрешение зависимости_) только
> в момент обращения к этому свойству – в частности при вызове
> метода `$classWithHeavyDependency->doHeavyDependency()`.

## Tag
Применятся к классу для тегирования.
```php
#[Tag(string $name, array $options = [], int|null|string $priority = null, ?string $priorityMethod = null)]
```
Аргументы:
- `$name` - имя тега.
- `$options` - метаданные для тега.
- `$priority` - приоритет для сортировки в коллекции тегов.
- `$priorityMethod` - метод класса для сортировки в коллекции тегов если неуказан `priority`.

> [!IMPORTANT]
> Метод указанный в аргументе `$priorityMethod` должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В качестве аргументов метод принимает два необязательных параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

> [!TIP]
> [Информация о сортировке по приоритету](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md#%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)
> для аргументов `priority`, `priorityMethod`.

Можно указать несколько атрибутов для класса:
```php
use Kaspi\DiContainer\Attributes\Tag; 
namespace App\Any;

#[Tag(name: 'tags.services.group-one', priorityMethod: 'getPriority')]
#[Tag(name: 'tags.services.group-two', priority: 1000)]
class SomeClass {}
```
> [!IMPORTANT]
> #️⃣ При использовании тегирования через PHP атрибуты необходимо чтобы
> класс был зарегистрирован в контейнере.
> Добавить в контейнер определения возможно через `DefinitionsLoader`
> используя [импорт и настройку сервисов из директорий](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md#%D0%B8%D0%BC%D0%BF%D0%BE%D1%80%D1%82-%D0%BA%D0%BB%D0%B0%D1%81%D1%81%D0%BE%D0%B2-%D0%B8%D0%B7-%D0%B4%D0%B8%D1%80%D0%B5%D0%BA%D1%82%D0%BE%D1%80%D0%B8%D0%B9).

> [!TIP]
> Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

## TaggedAs
Получение коллекции (_списка_) сервисов и определений отмеченных тегом.
Применяется к параметрам конструктора класса, метода или функции.
Тегирование класса в стиле php определенй через метод `bindTag` у [хэлпер функций](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#%D0%BE%D0%B1%D1%8A%D1%8F%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-%D1%85%D1%8D%D0%BB%D0%BF%D0%B5%D1%80-%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B8)
или через [php атрибут `#[Tag]`](#tag) у тегированного класса.

```php
#[TaggedAs(
    string $name,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
)]
```
Аргументы:
- `$name` – имя тега на сервисах которые нужно собрать из контейнера.
- `$isLazy` – получать сервисы только во время обращения или сразу всё.
- `$priorityDefaultMethod` – если получаемый сервис является php классом
  и у него не определен `priority` или `priorityMethod`, то будет выполнена попытка
  получить значение `priority` через вызов указанного метода.
- `$useKeys` – использовать именованные строковые ключи в коллекции.
  По умолчанию в качестве ключа элемента в коллекции используется идентификатор
  определения в контейнере (_container identifier_).
- `$key` – использовать ключ в коллекции для элемента из опций тега (_метаданные из `$options` определенные у тега_).
- `$keyDefaultMethod` – если получаемый сервис является php классом
  и у него не определен `$key`, то будет выполнена попытка
  получить значение ключа тега через вызов указанного метода.
- `$containerIdExclude` – исключить из коллекции определения
с указанными идентификаторами (_container identifier_).
- `$selfExclude` – исключить из коллекции php класс в который собирается коллекция
если он отмечен тем же тегом что и получаемая коллекция.

1. Подробнее [о приоритизации в коллекции.](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md#%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)
2. Подробнее [о ключах элементов в коллекции.](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md#%D0%BA%D0%BB%D1%8E%D1%87-%D1%8D%D0%BB%D0%B5%D0%BC%D0%B5%D0%BD%D1%82%D0%B0-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)

> [!IMPORTANT]
> Метод `$priorityDefaultMethod` должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В качестве аргументов метод принимает два необязательных параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

> [!IMPORTANT]
> Метод `$keyDefaultMethod` должен быть объявлен как `public static function`
> и возвращать тип `string`.
> В качестве аргументов метод принимает два необязательных параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

Пример получение «ленивой» коллекции из сервисов отмеченных тегом `tags.services.group_two`:
```php
// src/Classes/AnyClass.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class AnyClass {

    public function __construct(
        // будет получено как коллекция
        // с ленивой инициализацией сервисов
        #[TaggedAs(name: 'tags.services.group_two')]
        private iterable $services
    ) {}

}
```
Пример получение «ленивой» коллекции из классов реализующих интерфейс `App\Inerfaces\SomeInterface::class`:
```php
// src/Classes/SomeService.php
namespace App\Classes;

use App\Inerfaces\SomeInterface;
use Kaspi\DiContainer\Attributes\TaggedAs;

class SomeService {

    public function __construct(
        #[TaggedAs(
            name: SomeInterface::class,
            priorityDefaultMethod: 'getPriorityForSomeInterface'
        )]
        private iterable $services
    ) {}

}
```
Атрибут можно применять так же **параметрам переменной длинны**:
```php
// src/Classes/AnyService.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class AnyService {

    public function __construct(
        #[TaggedAs('tags.services.group_first', isLazy: false)]
        #[TaggedAs('tags.services.group_second', isLazy: false)]
        array ...$group
    ) {}

}
```
> [!WARNING]
> Для аргумента с типом `array` необходимо указать `$isLazy` как `false`.

> [!WARNING]
> Параметр переменной длинны является опциональным и если у него не задан
> PHP атрибут указывающий какой аргумент использовать
> для разрешения зависимости, то он будет пропущен.

> [!TIP]
> Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

## Разрешение зависимости объединенного типа через #[Inject].

Для объединенного типа (_union type_) контейнер попытается найти
доступные определения, и если будет найдено несколько вариантов
разрешения зависимости то будет выброшено исключение,
которое сообщит о необходимости уточнить тип для аргумента.
```php
// src/Classes/One.php
namespace App\Classes;

class One {}
```
```php
// src/Classes/Two.php
namespace App\Classes;

class Two {}
```
```php
// src/Services/Service.php
namespace App\Services;

use App\Classes\{One, Two};
use Kaspi\DiContainer\Attributes\Inject;

class Service {
 
    public function __construct(
        #[Inject]
        private One|Two $dependency
    ) {}

}
```
```php
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();

$container->get(App\Service\Service::class);
```
так как оба типа `App\Classes\One` и `App\Classes\Two` доступны для разрешения контейнером,
то будет выброшено исключение `\Psr\Container\ContainerExceptionInterface`.
В таком случае требуется конктретизировать тип:
```php
// src/Services/Service.php
namespace App\Services;

use App\Classes\{One, Two};
use Kaspi\DiContainer\Attributes\Inject;

class Service {
 
    public function __construct(
        #[Inject(Two::class)]
        private One|Two $dependency
    ) {}

}
```
```php
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();

$container->get(App\Services\Service::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Services\Service::class` в свойстве `App\Services\Service::$dependency`
> содержится класс `App\Classes\Two`.

## Пример #1
Заполнение коллекции на основе callback функции:

> 🚩 Похожий функционал можно реализовать [через тегированные определения](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).
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
// src/Services/IterableArg.php
namespace App\Services;

use App\Rules\RuleInterface;
use Kaspi\DiContainer\Attributes\Inject;

class IterableArg
{
    /**
     * @param App\Rules\RuleInterface[] $rules
     */
    public function __construct(
        #[Inject('services.rule-list')]
        private iterable $rules
    ) {}
}
```
```php
// config/services.php
use App\Rules\{RuleA, RuleB};

return static function (): \Generator {
    yield 'services.rule-list' => static fn (RuleA $a, RuleB $b) => \func_get_args();  
};
```
```php
use App\Services\IterableArg;
use Kaspi\DiContainer\{DefinitionsLoader, DiContainerFactory};

$loader = (new DefinitionsLoader())
    ->load(__DIR__.'/config/services.php');

$container = (new DiContainerFactory())->make(
    $loader->definitions()
);

$class = $container->get(IterableArg::class);
```

> [!TIP]
> Если требуется чтобы сервис `services.rule-list` был объявлен как `isSingleton`
> необходимо использовать функцию-хэлпер `diCallable`
> ```php
>   // config/services.php
>   use App\Rules\{RuleA, RuleB};
>   
>   return static function (): \Generator {
>       yield 'services.rule-list' => diCallable(
>           definition: static fn (RuleA $a, RuleB $b) => \func_get_args(),
>           isSingleton: true
>       );
>   };
> ```
