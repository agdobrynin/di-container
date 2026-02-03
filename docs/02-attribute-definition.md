# #️⃣ DiContainer c конфигурированием через PHP атрибуты

[В конфигурации контейнера](01-php-definition.md#конфигурирование_dicontainer) по умолчанию параметр `useAttribute` включён.

При конфигурировании контейнера можно совмещать php-атрибуты и php-определения.

> [!WARNING]
> При разрешении зависимостей в контейнере (_получение результата_) более высокой
> приоритет имеют php-атрибуты чем php-определения.
> 
> Если класс или интерфейс конфигурируется через php атрибуты
> и одновременно через файлы конфигурации, то при одинаковых идентификаторах
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
- **[DiFactory](#difactory)** – разрешение зависимости с помощью класса-фабрики.
- **[ProxyClosure](#proxyclosure)** – внедрение зависимости в параметры конструктора PHP класса, метода или аргументов функции с отложенной инициализацией через класс `\Closure`, анонимную функцию.
- **[Tag](#tag)** – определение тегов для класса.
- **[TaggedAs](#taggedas)** – внедрение тегированных определений в параметры конструктора, метода PHP класса.
- **[Параметр переменной длины](#параметр-переменной-длины)** – особенности применения атрибутов.

## Autowire
Применятся к классу для конфигурирования сервиса в контейнере.

```php
#[Autowire(string $id = '', ?bool $isSingleton = null, array $arguments = [])]
```
Параметры:
- `$id` – идентификатор контейнера для класса (_container identifier_).
- `$isSingleton` – зарегистрировать как singleton сервис. Если `null`, то значение будет выбрано на основе [настройки контейнера](../README.md#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).
- `$arguments` – предать аргументы для конструктора php класса.

> [!NOTE]
> Пустая строка в аргументе `$id` будет представлена как полное имя класса – **fully qualified class name** которая является идентификатором контейнера для этого php класса.

> [!TIP]
> - Для передачи неполного списка аргументов используйте в качестве ключа в массиве `$arguments` имя параметра в конструкторе php класса.
> - Для параметров не переданных через `$arguments` в php атрибуте, контейнер попытается разрешить зависимости самостоятельно на основе конфигурации.
> - Атрибут `#[Autowire]` имеет признак `repetable` и может быть применен несколько раз для одного и того же класса. 
> - При применении нескольких атрибутов `#[Autowire]` к php классу параметр `$id` у каждого атрибута должен быть уникальным, иначе выбрасывается исключение при разрешении класса контейнером.
> 

```php
// src/Services/FooService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire as DiAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use App\Interfaces\QuxInterface;
use App\Classes\{Foo, Bar};

#[
    Autowire(arguments: [
        'qux' => new DiGet(Foo::class),
    ]),
    Autowire(id: 'services.foo_baz', arguments: [
        'qux' => new DiAutowire(Bar::class),
    ]),
]
class FooService
{
    public function __construct(
        public readonly QuxInterface $qux
    ) {}
}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\FooService;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

var_dump($container->has(FooService::class)); // true
var_dump($container->has('services.foo_baz')); // true

var_dump(
    $container->get(FooService::class)->qux instanceof App\Classes\Foo
); // true

var_dump(
    $container->get(FooService::class)->qux instanceof App\Classes\Bar
); // true
```
> [!NOTE]
> При получении из контейнера по идентификатору `'App\Services\FooService'`
> в параметр `App\Services\FooService::$qux` разрешается объект `App\Classes\Foo`.
>
> При получении из контейнера по идентификатору `'services.foo_baz'`
> в параметр `App\Services\FooService::$qux` разрешается объект `App\Classes\Bar`.
>

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
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\SomeService;

$container = (new DiContainerBuilder())
    ->build()
;

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

Параметры:
- `$argument` - аргументы для передачи в вызываемый метод.

Значениями для `$argument` разрешается указывать скалярные типы данных,
массивы (array) содержащие скалярные типы, специальный тип null и объекты,
которые создают синтаксисом `new ClassName()`.

Для объектов передаваемых в качестве аргумента используются
классы описывающие определения контейнера:
- `Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire` – php класс
- `Kaspi\DiContainer\DiDefinition\DiDefinitionCallable` – `callable` тип
- `Kaspi\DiContainer\DiDefinition\DiDefinitionGet` – ссылка на идентификатор контейнера
- `Kaspi\DiContainer\DiDefinition\DiDefinitionValue` – определение «как есть».
- `Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure` – сервис через вызов `\Closure`
- `Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs` – тегированные определения

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
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use App\Rules\{RuleA, RuleB};

class RuleGenerator {

    private iterable $rules = [];
    
    #[Setup(inputRule: new DiGet(RuleB::class))]
    #[Setup(inputRule: new DiGet(RuleA::class))]
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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

## SetupImmutable

Применяется к методам PHP класса для настройки сервиса с учётом, 
что вызванный сеттер метод возвращает новый объект (_immutable setter method_).
Возвращаемое значение метода должно быть `self`, `static`
или того же класса, что и сам сервис.

```php
#[SetupImmutable(mixed ...$argument)]
```

Параметры:
- `$argument` - аргументы для передачи в вызываемый метод.

Значениями для `$argument` разрешается указывать скалярные типы данных,
массивы (array) содержащие скалярные типы, специальный тип null и объекты,
которые создают синтаксисом `new ClassName()`.

Для объектов передаваемых в качестве аргумента используются
классы описывающие определения контейнера:
- `Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire` – php класс
- `Kaspi\DiContainer\DiDefinition\DiDefinitionCallable` – `callable` тип
- `Kaspi\DiContainer\DiDefinition\DiDefinitionGet` – ссылка на идентификатор контейнера
- `Kaspi\DiContainer\DiDefinition\DiDefinitionValue` – определение «как есть».
- `Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure` – сервис через вызов `\Closure`
- `Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs` – тегированные определения

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
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use App\Loggers\MyLogger;
use Psr\Log\LoggerInterface;

class MyService
{
    private ?LoggerInterface $logger;

    #[SetupImmutable(logger: new DiGet(MyLogger::class))]
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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

$myService = $container->get(App\Services\MyService::class);

var_dump($myService->getLogger() instanceof Psr\Log\LoggerInterface); // true
```

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

> [!WARNING]
> При разрешении зависимости для составного типа (_union, intersection types_)
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

return static function (): \Generator {
    yield 'services.pdo-prod' => diAutowire(PDO::class)
        ->bindArguments(dsn: 'sqlite:/data/prod/db.db');

    yield 'services.pdo-local' => diAutowire(PDO::class)
        ->bindArguments(dsn: 'sqlite:/tmp/db.db');

    yield 'services.pdo-test' => diAutowire(PDO::class)
        ->bindArguments(dsn: 'sqlite::memory:');

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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->inputRule instanceof App\Rules\RuleA); // true
```

## InjectByCallable

Применяется к параметрам конструктора класса, метода или функции через `callable` тип.

```php
#[InjectByCallable(callable $callable)]
```
Параметры:
- `$callable` - выполнение `callable` типа для получения результата внедрения.

> [!TIP]
> Параметры указанные в `callable` вызове могут быть разрешены
> контейнером автоматически.

Пример использования:
```php
// src/Classes/One.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Inject;

class One {
    
    public function __construct(private string $code) {}
    
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
        #[InjectByCallable([App\Classes\One::class, 'config'])]
        private One $one
    ) {}

}
```
```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerBuilder;

$definitions = [
    'config.secure_code' => 'abc',
];

$container = (new DiContainerBuilder())
    ->addDefinitions($definitions)
    ->build()
;

// Получение данных из контейнера
$service = $container->get(App\Services\ServiceOne::class);
```
> [!NOTE]
> При разрешении параметров конструктора класса `App\Services\ServiceOne::class` в свойстве
> `App\Services\ServiceOne::$one` будет класс `App\Classes\One`
> у которого в свойстве `App\Classes\One::$code` строка `'abc'`
> полученная при создании класса в статическом методе `App\Classes\One::config()`.

> [!TIP]
> Объявить строку для аргумента `$callable` у php атрибута `#[InjectByCallable]`
> можно используя через безопасное объявление класса – магическую константу
> `::class`:
> 1. в виде строки для параметра `$one`;
> 2. в виде массива являющегося `callable` типом для параметра `$two`;
> 
> ```php
>   namespace App\Services;
> 
>   use Kaspi\DiContainer\Attributes\InjectByCallable;
>   use App\Classes\One;
> 
>   class ServiceOne {
>
>       public function __construct(
>           #[InjectByCallable(One::class.'::config')]
>           private One $one,
>           #[InjectByCallable([One::class, '::config'])]
>           private One $tow
>       ) {}
> 
>   }
> ```

## Service

Применяется к интерфейсу для конфигурирования реализации php интерфейса.
```php
#[Service(string $id, ?bool $isSingleton = null)]
```
Параметры:
- `$id` - класс реализующий интерфейс (FQCN) или идентификатор контейнера.
- `$isSingleton` - зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](../README.md#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

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
        // подставить в параметр $file в конструкторе.
        ->bindArguments(file: '/var/log/app.log');
};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\Loggers\MyLogger::class);

print $myClass->customLogger->loggerFile(); // /var/log/app.log
```

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
Атрибут может применяться к классу или к параметру функции, метода.

Сигнатура php атрибута:
```php
#[DiFactory(string|array $definition, ?bool $isSingleton = null, array $arguments = [])]
```
Параметры:
- `$definition` – представление php класса и метода фабрики.
- `$isSingleton` – зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](../README.md#конфигурирование-dicontainer).
- `$arguments` – предать аргументы для метода фабрики.

> [!NOTE]
> Параметр атрибута `$isSingleton` при применении к параметрам метода (функции) будет проигнорирован
> и не используется при разрешении зависимостей.
>

> [!NOTE]
> Подробное [описание работы с фабриками](07-factory.md) для разрешения зависимостей в контейнере.

## ProxyClosure

Реализация ленивой инициализации параметров класса (зависимости) через функцию обратного вызова.
Применяется к параметрам конструктора класса, метода или функции.

```php
#[ProxyClosure(string $containerIdentifier)]
```
Параметры:
- `$containerIdentifier` - идентификатора контейнера (php класс, интерфейс) реализующий сервис который необходимо разрешить отложено.

Такое объявление сервиса пригодится для «тяжёлых» зависимостей, требующих длительного времени инициализации или ресурсоёмких вычислений.

> [!TIP]
> Подробное объяснение использования [ProxyClosure](01-php-definition.md#diproxyclosure)

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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

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
Параметры:
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
> [Информация о сортировке по приоритету](05-tags.md#%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)
> для аргументов `priority`, `priorityMethod`.

Можно указать несколько атрибутов для класса:
```php
use Kaspi\DiContainer\Attributes\Tag; 
namespace App\Any;

#[Tag(name: 'tags.services.group-one', priorityMethod: 'getPriority')]
#[Tag(name: 'tags.services.group-two', priority: 1000)]
class SomeClass {}
```

> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

## TaggedAs
Получение коллекции (_списка_) сервисов и определений отмеченных тегом.
Применяется к параметрам конструктора класса, метода или функции.
Тегирование класса в стиле php определенй через метод `bindTag` у [хэлпер функций](01-php-definition.md#%D0%BE%D0%B1%D1%8A%D1%8F%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-%D1%85%D1%8D%D0%BB%D0%BF%D0%B5%D1%80-%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B8)
или через [php атрибут `#[Tag]`](#tag) у тегированного класса.

Результат выполнения может быть применен для параметров с типом:
- `iterable`
  - `\Traversable`
    - `\Iterator`
- `\ArrayAccess`
- `\Psr\Container\ContainerInterface`
- `array` требуется использовать параметр `$isLazy = false`.
- Составной тип (_intersection types) для ленивых коллекций (`$isLazy = true`)
  - `\ArrayAccess&\Iterator&\Psr\Container\ContainerInterface`.

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
Параметры:
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
- `$selfExclude` – исключить из коллекции php класс, в который собирается коллекция
если он отмечен тем-же тегом, что и получаемая коллекция.

1. Подробнее [о приоритизации в коллекции.](05-tags.md#%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)
2. Подробнее [о ключах элементов в коллекции.](05-tags.md#%D0%BA%D0%BB%D1%8E%D1%87-%D1%8D%D0%BB%D0%B5%D0%BC%D0%B5%D0%BD%D1%82%D0%B0-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)

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
Атрибут можно применять так же **параметрам переменной длины**:
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
> Параметр переменной длины является опциональным и если у него не задан
> PHP атрибут указывающий какой аргумент использовать
> для разрешения зависимости, то он будет пропущен.

> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

## Параметр переменной длины.
При разрешении зависимостей параметра переменной длины у метода или функции можно использовать
комбинации PHP атрибутов.

Проверка типа (_type hint_) разрешаемой зависимости производится на уровне вызова метода или функции – в момент выполнения.

Пример:

```php
namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use App\Factories\ServiceOneFactory;

final class Foo {
    public function __construct(
        #[Inject('service.foo_bar')]
        #[DiFactory(ServiceOneFactory::class)]
        #[InjectByCallable('\uniqid')]
        mixed ...$args
    ) {}
}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

$foo = $container->get(\App\Services\Foo::class);
```
> [!NOTE]
> При разрешении параметров конструктора `App\Services\Foo::class` в свойстве `App\Services\Foo::$args`
> будут разрешены следующие зависимости:
> - `App\Services\Foo::$args[0]` – получен сервис через метод контейнера `get('service.foo_bar')`;
> - `App\Services\Foo::$args[1]` – получен результат выполнения класса-фабрики `\App\Factories\ServiceOneFactory`;
> - `App\Services\Foo::$args[2]` – получен результат вызова `callable` типа: выполнение функции `\uniqid()`;

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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

$container->get(App\Services\Service::class);
```
> [!NOTE]
> При разрешении параметров конструктора `App\Services\Service::class` в свойстве `App\Services\Service::$dependency`
> содержится класс `App\Classes\Two`.

## Пример #1
Заполнение коллекции на основе callback функции:

> 🚩 Похожий функционал лучше реализовать [через тегированные определения](05-tags.md).
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
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

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
