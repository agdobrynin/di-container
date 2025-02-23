# #️⃣ DiContainer c конфигурированием через PHP атрибуты

[В конфигурации контейнера](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#конфигурирование_dicontainer) по умолчанию параметр `useAttribute` включён.

При конфигурировании контейнера можно совмещать php-атрибуты и php-определения.

> [!WARNING]
> При разрешении зависимостей в контейнере (_получение результата_) более высокой
> приоритет имеют php-атрибуты чем php-определения.

Доступные атрибуты:
- **[Inject](#inject)** - внедрение зависимости в параметры конструктора, метода класса.
- **[Service](#service)** - определение для интерфейса какой класс будет вызван и разрешен в контейнере.
- **[DiFactory](#difactory)** - фабрика для c помощью которой разрешается зависимость класса. Класс должен реализовывать интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`
- **[ProxyClosure](#proxyclosure)** - внедрение зависимости в параметры с отложенной инициализацией через функцию обратного вызова `\Closure`.
- **[Tag](#tag)** - определение тегов для класса.
- **[TaggedAs](#taggedas)** - внедрение тегированных определений в параметры конструктора, метода класса.

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

### Атрибут #[Inject] для получения по идентификатору контейнера в конструкторе:

```php
// Объявление класса
use Kaspi\DiContainer\Attributes\Inject;

namespace App;

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

// ... more code ...

// PDO будет указывать на базу sqlite:/tmp/db.db'
$myClass = $container->get(App\MyDb::class);
```

### Атрибут #[Inject] для разрешения аргументов переменной длины

Атрибут имеет признак `repetable`

```php
// Объявления классов
use Kaspi\DiContainer\Attributes\Inject;

namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}

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
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

### Атрибут #[Inject] для аргументов переменной длины по идентификатору контейнера

```php
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}

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
// определения для контейнера
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\{diAutowire, diCallable};

$definitions = [
    'services.rules.a' => diCallable(
        // Автоматически внедрит зависимости этой callback функции
        static function (App\Rules\RuleA $a) {
            // тут возможны дополнительные настройки объекта
            return $a
        }
    ),

    'services.rules.b' => diAutowire(App\Rules\RuleB::class),
];

$container = (new DiContainerFactory())->make($definitions);

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

### Атрибут #[Inject] и класс реализующий DiFactoryInterface
Класс реализующий `Kaspi\DiContainer\Interfaces\DiFactoryInterface` будет вызван контейнером и исполнен метод `__invoke`
который является результатом для Inject атрибута.

Пример применения для аргументов переменной длинны:
```php
// Объявления классов
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}

class RulesDiFactory implements DiFactoryInterface {

    public function __construct(
        private RuleA $ruleA,
    ) {}

    public function __invoke(ContainerInterface $container): RuleA {
        // тут возможны дополнительные настройки объекта ruleA
        return $this->ruleA;
    }
}

class RuleGenerator {

    private iterable $rules;

    public function __construct(
        #[Inject(RuleB::class)]
        #[Inject(RulesDiFactory::class)]
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
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make();

// ... more code

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

var_dump($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true
var_dump($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true
```

### Атрибут **#[Inject]** при внедрении класса для интерфейса.

```php
// Объявления классов
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

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

## Service

Применяется к интерфейсу.
```php
#[Service(string $id, bool $isSingleton = false)]
```
Аргументы:
- `$id` - класс реализующий интерфейс (FQCN) или идентификатор контейнера.
- `$isSingleton` - зарегистрировать как singleton сервис.

> [!NOTE]
> **FQCN** - fully Qualified Class Name. 

```php
// Объявление классов
use Kaspi\DiContainer\Attributes\InjectByReference;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;

namespace App;

#[Service(CustomLogger::class)] // класс реализующий данный интерфейс.
interface CustomLoggerInterface {
    public function loggerFile(): string;
}

// Класс реализующий CustomLoggerInterface.

class CustomLogger implements CustomLoggerInterface {

    public function __construct(
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}

// Класс внедряющий зависимость по CustomLoggerInterface

class MyLogger {

    public function __construct(
        // Контейнер найдёт интерфейс
        // и проверит у него php-атрибут Service.
        public CustomLoggerInterface $customLogger
    ) {}
}
```

```php
// Определения для DiContainer
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\diAutowire;

$definitions = [
    diAutowire(App\CustomLogger::class)
        // 🌞 подставить в параметр $file в конструкторе.
        ->bindArguments(file: '/var/log/app.log')
];

$container = (new DiContainerFactory())->make($definitions);
```

```php
// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\MyLogger::class);
print $myClass->customLogger->loggerFile(); // /var/log/app.log
```
Так же атрибут Service можно использовать со ссылкой на другой идентификатор контейнера.
```php
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\diCallable;

#[Service('services.my-srv')] // 🔃 Получить по идентификатору контейнера
interface MyInterface {}

// ...

class SuperClass {

    public function __construct(public MyInterface $my) {}
}

// ...

class SuperSrv implements MyInterface {

    public function changeConfig(array $config) {
        //...
    }
}

// ...
$definitions = [
    'services.my-srv' => diCallable(static function (SuperSrv $srv) {
        $srv->changeConfig([...]); // какие-то дополнительные настройки.
        
        return $srv; // вернуть настроенный сервис.
    }),
];

$container = (new DiContainerFactory())->make($definitions);

$container->get(SuperClass::class); 
```

## DiFactory
Применятся к классу для создания определения.
```php
#[DiFactory(string $id, bool $isSingleton = false)]
```
Аргументы:
- `$id` - класс (_FQCN_) реализующий интерфейс `Kaspi\DiContainer\Interfaces\DiFactoryInterface`.
- `$isSingleton` - создать сервис как singleton.
```php
// Определение класса
use Kaspi\DiContainer\Attributes\DiFactory

namespace App;

// Разрешить зависимость через фабрику и указать контейнеру что это будет Singleton.
#[DiFactory(App\Factory\FactorySuperClass::class, isSingleton: true)]
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

$container = (new DiContainerFactory())->make();

$myClass = $container->get(SuperClass::class);
print $myClass->name; // Piter
print $myClass->age; // 22
```
## ProxyClosure

Реализация ленивой инициализации параметров класса (зависимости) через функцию обратного вызова.

```php
#[ProxyClosure(string $id, bool $isSingleton = false)]
```
Аргументы:
- `$id` - класс (_FQCN_) реализующий сервис который необходимо разрешить отложено.
- `$isSingleton` - создать сервис как singleton.

Такое объявление сервиса пригодится для «тяжёлых» зависимостей, требующих длительного времени инициализации или ресурсоёмких вычислений.

> [!TIP]
> Подробное объяснение использования [ProxyClosure](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#diproxyclosure)

Пример для отложенной инициализации сервиса через атрибут `#[ProxyClosure]`:

```php
// Класс с «тяжёлыми» зависимостями, много ресурсов на инициализацию.
namespace App\Services;

use Kaspi\DiContainer\Attributes\ProxyClosure;

class HeavyDependency {
    public function __construct(...) {}
    public function doMake() {}
}

class ClassWithHeavyDependency {
    /**
     * @param Closure(): HeavyDependency $heavyDependency
     */
    public function __construct(
        /**
         * 🚩 Подсказка для IDE при авто-дополении (autocomplete).
         * @param Closure(): HeavyDependency $heavyDependency
         */
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

// свойство ClassWithHeavyDependency::$heavyDependency
// ещё не инициализировано.
$classWithHeavyDependency = $container->get(App\Services\ClassWithHeavyDependency::class);

// Внутри метода инициализируется
// свойство ClassWithHeavyDependency::$heavyDependency
// через Closure вызов (callback функция) 
$classWithHeavyDependency->doHeavyDependency();
```

## Tag
Применятся к классу для определения тега.
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

> [!TIP]
> Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

## TaggedAs
Получение коллекции (списка) сервисов и определений отмеченных тегом.
Определение тегов в стиле php определенй через метод `bindTag` у [хэлпер функций](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#%D0%BE%D0%B1%D1%8A%D1%8F%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-%D1%85%D1%8D%D0%BB%D0%BF%D0%B5%D1%80-%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B8)
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

**Пример получение «ленивой» коллекции из сервисов отмеченных тегом `tags.services.group-two`:**
```php
use Kaspi\DiContainer\Attributes\TaggedAs;

class AnyClass {

    public function __construct(
        // будет получено как коллекция через тип \Generator
        // с ленивой инициализацией сервисов
        #[TaggedAs(name: 'tags.services.group-two')]
        private iterable $services
    ) {}

}

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
Чтобы заполнить параметр с типом `array` необходимо указать аргумент `$isLazy` как `false`:
```php
use Kaspi\DiContainer\Attributes\TaggedAs;

class AnyClass {

    public function __construct(
        // будет получено массив (все сервисы уже получены)
        #[TaggedAs(name: 'tags.services.group-two', isLazy: false)]
        private array $services 
    ) {}

}
```
Атрибут можно применять так же **параметрам переменной длинны**:
```php
use Kaspi\DiContainer\Attributes\TaggedAs;

class AnyService {

    public function __construct(
        #[TaggedAs('tags.word-group.first', false)]
        #[TaggedAs('tags.word-group.second', false)]
        array ...$wordGroup
    ) {}

}
```
> [!TIP]
> Более подробное [описание работы с тегами](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

## Пример #1
Заполнение коллекции на основе callback функции:

> 🚩 Похожий функционал можно реализовать [через тегированные определения](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).
```php
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}
```
```php
namespace App\Services;

use App\Rules\RuleInterface;
use Kaspi\DiContainer\Attributes\Inject;

class IterableArg
{
    /**
     * @param RuleInterface[] $rules
     */
    public function __construct(
        #[Inject('services.rule-list')]
        private iterable $rules
    ) {}
}
```
```php
use App\Rules\{RuleA, RuleB}; 
use App\Services\IterableArg;
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make([
    'services.rule-list' => static fn (RuleA $a, RuleB $b) => \func_get_args(),
]);

$class = $container->get(IterableArg::class);
```

> [!TIP]
> Если требуется чтобы сервис `services.rule-list` был объявлен как `isSingleton`
> необходимо использовать функцию-хэлпер `diCallable`
> ```php
> $definitions = [
>   'services.rule-list' => diCallable(
>       definition: static fn (RuleA $a, RuleB $b) => \func_get_args(),
>       isSingleton: true
>   ),
> ];
> ```
