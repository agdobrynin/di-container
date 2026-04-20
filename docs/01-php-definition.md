# 🐘 DiContainer с конфигурированием в стиле php определений

Получение существующего класса и разрешение параметров в конструкторе.

Класс где необходимо разрешить зависимость `$pdo` в конструкторе
с помощью контейнера:
```php
// src/Classes/MyClass.php
namespace App\Classes;

class MyClass {
    public function __construct(public \PDO $pdo) {}
}
```
Конфигурационный файл для контейнера:
```php
// config/services.php
use function Kaspi\DiContainer\diAutowire;

return static function (): \Generator {
    // хэлпер функция для объявления зависимости
    yield diAutowire(
        // получить класс \PDO 
        definition: \PDO::class,
        // всегда возвращать тот же объект
        isSingleton: true
        )
            // установить параметр $dsn в конструкторе 'sqlite:/tmp/my.db'.
            ->bindArguments(
                dsn: 'sqlite:/tmp/my.db'
            )
            // Вызвать метод "setAttribute" и предать параметры в него
            ->setup('setAttribute', [\PDO::ATTR_CASE, \PDO::CASE_UPPER]),

};
```
Создание контейнера зависимостей:

```php
use Kaspi\DiContainer\{DiContainerBuilder, DiContainerConfig};

// конфигурирование контейнера.
$config = new DiContainerConfig();

// получение готового контейнера зависимостей.
$container = (new DiContainerBuilder(containerConfig: $config))
    ->load(__DIR__.'/config/services.php')
    ->build()
;

// Получение данных из контейнера с автоматическим разрешением зависимостей
$myClass = $container->get(App\Classes\MyClass::class); // $pdo->dsn === 'sqlite:/tmp/my.db' 

$myClass->pdo->query('...');

// получать один и тот же объект PDO::class
// так как в определении указан isSingleton=true
$myClassTwo = $container->get(App\MyClass::class);

var_dump(
    \spl_object_id($myClass->pdo) === \spl_object_id($myClassTwo->pdo)
); // true
```
> [!NOTE]
> Для примера выше фактически будет выполнен следующий php код:
> ```php
> $pdo = new \PDO(dns: 'sqlite:/tmp/my.db');
> $pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_UPPER);
> $service = new App\MyClass($pdo);
> $service->pdo->query('...') // готовый сервис для использования
> ```

> [!TIP]
> Для создания настроенного контейнера используется 
> [класс-строитель `DiContainerBuilder`](06-container-builder.md).

> [!TIP]
> Реализация кода в [примере](../examples/01-01-pdo.php)

## Объявления для определений контейнера.

### Объявления через хэлпер функции:
- хэлпер функции:
  - [diAutowire](#diautowire) – php класс
  - [diCallable](#dicallable) – `callable` тип
  - [diGet](#diget) – ссылка на идентификатор контейнера
  - [diValue](#divalue) – определение «как есть».
  - [diProxyClosure](#diproxyclosure) – сервис через вызов `\Closure`
  - [diTaggedAs](#ditaggedas) – тегированные определения
  - [diFactory](#difactory) – фабрика для разрешения зависимости
  - [diParameter](#diparameter) – параметр контейнера
  - [diParameterRuntime](#diparameterruntime) – параметр контейнера времени исполнения

> [!TIP]
> ✅ Для параметров методов php классов или `callable` типов можно указывать [скалярные типы](https://www.php.net/manual/ru/language.types.type-system.php#language.types.type-system.atomic.scalar),
`null`, перечисляемые типы, «как есть» – без указания как разрешить зависимость.
> Для повторяющихся значений рекомендуется использовать «[параметры контейнера](09-container-parameters.md)». 

> [!NOTE]
> Хэлпер функции имеют отложенную инициализацию параметров поэтому минимально влияют на начальную загрузку контейнера.

#### diAutowire

Автоматическое создание объекта и внедрения зависимостей.

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\{DiDefinitionSetupAutowireInterface, DiDefinitionTagArgumentInterface};
use function \Kaspi\DiContainer\diAutowire;

diAutowire(string $definition, ?bool $isSingleton = null): DiDefinitionSetupAutowireInterface & DiDefinitionTagArgumentInterface
```
Параметры:
- `$definition` – имя класса с пространством имен представленный строкой. Можно использовать безопасное объявление через магическую константу `::class` - `MyClass::class`
- `$isSingleton` – зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](../README.md#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

> [!IMPORTANT]
> Функция `diAutowire` возвращает объект реализующий интерфейсы
> `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface`
> и `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`.
> 
> Интерфейсы представляют методы:
>   - `bindArguments` - аргументы для конструктора класса
>   - `setup` - вызов метода класса с параметрами (_mutable setter method_) для настройки класса
>   - `setupImmutable` - вызов метода класса с параметрами (_immutable setter method_) и возвращаемым значением
>   - `bindTag` - добавляет тег с мета-данными для определения
 
**Аргументы для конструктора:**
```php
bindArguments(mixed ...$argument)
```
Параметры:
- `$argument` – аргументы к параметрам конструктора класса

> [!TIP]
> ✅ Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».
 
> [!WARNING]
> метод перезаписывает ранее определенные аргументы.
 
Можно использовать именованные аргументы параметров:
```php 
diAutowire(...)->bindArguments(var1: 'value 1', var2: 'value 2')
// public function __construct(string $var1, string $var2) {}
```
> [!TIP]
> Для параметров не объявленных через `bindArgument` контейнер попытается разрешить зависимости самостоятельно.

> [!TIP]
> Аргумент `$argument` в `bindArgument` может принимать хэлпер функции такие как `diGet`, `diValue`, `diAutowire` и другие.
>
> Если в `$argument` присваивается хэлпер функция или объект реализующий интерфейс
> `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface::class`
> (например `Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire::class`)
> то признак isSingleton будет проигнорирован при разрешении зависимости данного параметра.

**Дополнительная настройка сервиса через методы класса (mutable setters):**
```php 
setup(string $method, array $arguments = [])
``` 
Параметры:
- `$method` – имя вызываемого метода в классе
- `$arguments` – аргументы к параметрам метода класса

Возвращаемое значение из вызываемого метода не учитывается при настройке сервиса,
контейнер вернет экземпляр класса созданного через конструктор класса.

> [!TIP]
> Для аргументов не объявленных через `setup` контейнер по попытается разрешить зависимости автоматически на основе конфигурации.

> [!TIP]
> Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».

> [!TIP]
> Аргументы метода в `setup` могут принимать хэлпер функции такие как `diGet`, `diValue`, `diAutowire` и другие.

Можно использовать именованные аргументы параметров:
```php
diAutowire(...)->setup('classMethod', ['var1' => 'value 1', 'var2' => 'value 2'])
// $object->classMethod(var1: 'value 1', var2: 'value 2')
```
Если в методе нет параметров или они могут быть разрешены автоматически, то аргументы указывать не нужно:
```php
   diAutowire(...)
       ->bindArguments(...)
       ->setup('classMethodWithoutParams')
   // $object->classMethodWithoutParams(SomeDependency $someDependency)
```
При указании нескольких вызовов метода он будет вызван указанное количество раз и возможно с разными аргументами:
```php
diAutowire(...)
  ->setup('classMethod', ['var1' => 'value 1', 'var2' => 'value 2'])
  ->setup('classMethod', ['var1' => 'value 3', 'var2' => 'value 4')]
  // $object->classMethod(var1: 'value 1', var2: 'value 2');
  // $object->classMethod(var1: 'value 3', var2: 'value 4');
```
 
> [!NOTE]
> [пример использования метода `diAutowire(...)->setup`](#пример-4)

**Дополнительная настройка сервиса через методы класса возвращающие значение (immutable setters):**
```php 
setupImmutable(string $method, array $arguments = [])
``` 
Параметры:
- `$method` – имя вызываемого метода в классе
- `$arguments` – аргументы к параметрам метода класса

Возвращаемое значение метода должно быть `self`, `static`
или того же класса, что и сам php класс.
Контейнер вернет экземпляр класса созданного через вызываемый метод.

> [!TIP]
> Для аргументов не объявленных через `setupImmutable` контейнер по попытается разрешить зависимости автоматически на основе конфигурации.

> [!TIP]
> Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».

> [!TIP]
> Аргументы в `setupImmutable` могут принимать хэлпер функции такие как `diGet`, `diValue`, `diAutowire` и другие.

> [!NOTE]
> [пример использования метода `diAutowire(...)->setupImmutable`](#пример-5)
> 
**Указать теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```

```php
  diAutowire(...)
      ->bindTag('tags.rules', priority: 100)
```
> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

##### Идентификатор контейнера для diAutowire.
При конфигурировании идентификатор контейнера может быть сформирован на основе FQCN  (**Fully Qualified Class Name**)

```php
// config/services_without_id.php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    // идентификатор контейнера сформируется
    // из имени класса включая пространство имен
    yield diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn')
        ),
    );
};
```
```php
// эквивалентно
// config/services_with_id.php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    yeild \PDO::class => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn')
        );
};
```
Если необходим другой идентификатор контейнера, то можно указывать так:
```php
// /app/config/parameters.php
return [
    'db.dsn_file' => 'sqlite:/tmp/my.db',
    'db.dsn_memory' => 'sqlite::memory:',
];
```
```php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    // $container->get('pdo-in-tmp-file')
    yield 'pdo-in-tmp-file' => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn_file')
        );

    // $container->get('pdo-in-memory')
    yield 'pdo-in-memory' => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn_memory')
        );
};
```
#### diCallable
Получение результата обработки `callable` типа.
```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use function \Kaspi\DiContainer\diCallable; 

diCallable(callable $definition, ?bool $isSingleton = null): DiDefinitionArgumentsInterface
```
Параметры:
- `$definition` – определение.
- `$isSingleton` – зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](../README.md#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

> [!IMPORTANT]
> Функция `diCallable` возвращает объект реализующий интерфейс `DiDefinitionArgumentsInterface`
> предоставляющий методы:
> - `bindArguments` - указать аргументы для параметров функции.
> - `bindTag` - добавляет тег с мета-данными для определения.

**Аргументы для определения:**
```php
bindArguments(mixed ...$argument)
```
Параметры:
- `$argument` – аргументы к параметрам метода класса

> [!TIP]
> Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».

Можно использовать именованные аргументы параметров
 ```php
 bindArguments(var1: 'value 1', var2: 'value 2');
 // function(string $var1, string $var2) 
 ```
> [!TIP]
> Аргумент `$argument` в `bindArgument` может принимать хэлпер функции такие как `diGet`, `diValue`, `diAutowire` и другие.
>
> Если в `$argument` присваивается хэлпер функция или объект реализующий интерфейс
> `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface::class`
> (например `Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire::class`)
> то признак isSingleton будет проигнорирован при разрешении зависимости данного параметра.


> [!WARNING]
> метод `bindArguments` перезаписывает ранее определенные аргументы.

**Указать теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```
> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

##### Идентификатор контейнера.
Если нужно объявить определение в контейнере то необходимо указать в конфигурации идентификатор контейнера.

**Пример.**

Объявление класса:
```php
// src/Services/ServiceOne.php
namespace App\Services;

class ServiceOne {

    public function __construct(private string $apiKey, private bool $debug) {}

    public static function makeForTest(string $apiKey): self {
        return new self($apiKey, true)
    }
    // some methods here
}
```
Файл конфигураций параметров:
```php
// /app/config/parameters/api_keys.php
return [
    'api_key' => 'value_api_key',
    'api_key.other' => 'other_value_api_key',    
];
```
Файл конфигураций определений:
```php
// /app/config/services.php
use function \Kaspi\DiContainer\{diCallable, diParameter};

require static function (): \Generator {

    yield 'services.one' => diCallable(
        definition: static fn (string $apiKey) => new App\Services\ServiceOne($apiKey, false),
        isSingleton: true,
    )
        ->bindArguments(
            diParameter('api_key')
        );

    yield 'services.two' => diCallable(
        definition: [App\Services\ServiceOne::class, 'makeForTest'],
        isSingleton: false, 
    )
        // Получить значение аргумента для ServiceOne::makeForTest()
        ->bindArguments(
            apiKey: diParameter('api_key.other')
        );

};
```

Разрешение зависимостей через контейнер:

```php
use \Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters(__DIR__.'/config/parameters/api_keys.php')
    ->load(__DIR__.'/config/services.php')
    ->build()
;

// ...

var_dump($container->get('services.one') instanceof App\Services\ServiceOne); // true
var_dump($container->get('services.two') instanceof App\Services\ServiceOne); // true
```
> [!NOTE]
> Для сервиса `'services.one'` значение в свойстве `App\Services\ServiceOne::$apiKey`
> будет `'value_api_key'`.
> Для сервиса `'services.two'` значение в свойстве `App\Services\ServiceOne::$apiKey`
> будет `'other_value_api_key'`.


> [!TIP]
> Так же доступно объявление через callback функцию которое будет корректно:
> ```php
> // для примера из config/services.php
> // ...
>   yield 'services.one' => static fn () => new App\Services\ServiceOne(apiKey: 'value_api_key', debug: false),
> // ....
> ```

> [!TIP]
> Если у определения объявленного через `diCallable` присутствуют аргументы,
> то они могут быть разрешены контейнером автоматически включая [использование php атрибутов](02-attribute-definition.md).

#### diGet
Определение как ссылки на другой идентификатор контейнера.

```php
use function \Kaspi\DiContainer\diGet;
 
diGet(string $containerIdentifier)
```
Аргумент:
- `$containerIdentifier` - содержит указание на идентификатор контейнера, или указание на php класс.

> У хэлпер функции нет дополнительных методов.

**Пример с указанием на идентификатор контейнера.**
```php
// config/services.php
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;

return static function (): \Generator {

    yield 'services.env-dsn' => diCallable(
        definition: static function () {
            return match (getenv('APP_ENV')) {
                'prod' => 'sqlite:/databases/my-app/app.db',
                'test' => 'sqlite::memory:',
                default => 'sqlite:/tmp/mydb.db',  
            };
        },
        isSingleton: true
    );

    // ...

    yield diAutowire(\PDO::class)
        // получить значение для аргумента
        // через ссылку на идентификатор контейнера
        ->bindArguments(dsn: diGet('services.env-dsn'));
  
};
```

#### diValue

Определение без обработки — «как есть».

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use function \Kaspi\DiContainer\diValue;
 
diValue(mixed $value): DiDefinitionTagArgumentInterface
```

> [!IMPORTANT]
> Функция `diValue` возвращает объект реализующий интерфейс `DiDefinitionTagArgumentInterface`
> предоставляющий метод:
> - `bindTag` - добавляет тэг с мета-данными для определения.

**Указать теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```
> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

##### Идентификатор контейнера для `diValue`.
При объявлении зависимости через `diValue` необходимо указать в конфигурации идентификатор контейнера.

```php
// config/emails.php
use function Kaspi\DiContainer\diValue;

return static function () {

    yield 'admin.email.tasks' => diValue('runner@company.inc');

};
```

#### diProxyClosure

Определение для отложенной инициализации сервиса через Closure тип.

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use function Kaspi\DiContainer\diProxyClosure;

diProxyClosure(string $containerIdentifier, ?bool $isSingleton = null): DiDefinitionTagArgumentInterface
```
Параметры:

- `$containerIdentifier` - идентификатора контейнера (php класс, интерфейс) реализующий сервис который необходимо разрешить отложено.
- `$isSingleton` – зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](../README.md#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

> [!IMPORTANT]
> Функция `diProxyClosure` возвращает объект реализующий интерфейс `DiDefinitionTagArgumentInterface`
> предоставляющий метод:
> - `bindTag` - добавляет тэг с мета-данными для определения.

**Указать теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```
> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

##### Идентификатор контейнера.
Если нужно объявить определение в контейнере, то необходимо указать в конфигурации идентификатор контейнера.

```php
// config/services.php
use App\Classes\HeavyDependency;
use function Kaspi\DiContainer\diProxyClosure;

return static function(): \Generator {

    yield 'services.heavy_dependency' => diProxyClosure(HeavyDependency::class, isSingleton: true)

};
```

##### Пример для отложенной инициализации сервиса как аргумента.

Такое объявление сервиса пригодится для «тяжёлых» зависимостей,
требующих длительного времени инициализации или ресурсоёмкий вычислений.
```php
// src/Classes/HeavyDependency.php
namespace App\Classes;

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

class ClassWithHeavyDependency {
    /**
     * @param Closure(): HeavyDependency $heavyDependency
     */
    public function __construct(
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
```php
// config/services.php
use App\Classes\{ClassWithHeavyDependency, HeavyDependency};
use function Kaspi\DiContainer\diProxyClosure;

return static function(): \Generator {

    yield diAutowire(ClassWithHeavyDependency::class)
        ->bindArguments(
            heavyDependency: diProxyClosure(HeavyDependency::class),
        );

};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$classWithHeavyDep = $container->get(App\Classes\ClassWithHeavyDependency::class);

$classWithHeavyDep->doHeavyDependency();
```
> [!NOTE]
> При разрешении зависимости контейнера `App\Classes\ClassWithHeavyDependency::class`
> свойство в классе `ClassWithHeavyDependency::$heavyDependency` ещё не инициализировано.
> Инициализация произойдёт (_разрешение зависимости_) только
> в момент обращения к этому свойству – в частности при вызове
> метода `$classWithHeavyDependency->doHeavyDependency()`.

> [!TIP]
> Для подсказок IDE autocomplete можно использовать PhpDocBlock:
> ```php
>  /**
>   * 🚩 Подсказка для IDE при авто-дополении (autocomplete).
>   * @param Closure(): HeavyDependency $heavyDependency
>   */
>   public function __construct(
>       private \Closure $heavyDependency,
>       private LiteDependency $liteDependency,
>   ) {}
> ```
#### diTaggedAs
Определение для получения коллекции сервисов отмеченных тегом.
Результат выполнения может быть применен для параметров с типом:
 - `iterable`
   - `\Traversable`
     - `\Iterator`
 - `\ArrayAccess`
 - `\Psr\Container\ContainerInterface`
 - `array` требуется использовать параметр `$isLazy = false`.
 - Составной тип (_intersection types_) для ленивых коллекций (`$isLazy = true`)
   - `\ArrayAccess&\Iterator&\Psr\Container\ContainerInterface`. 
```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use function Kaspi\DiContainer\diTaggedAs;

diTaggedAs(
    string $tag,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
): DiDefinitionNoArgumentsInterface
```
> У хэлпер функции нет дополнительных методов.

Параметры:
- `$tag` – имя тега на сервисах которые нужно собрать из контейнера.
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
- `$selfExclude` – исключить из коллекции php-класс в который собирается коллекция
  если он отмечен тем же тегом что и получаемая коллекция.


1. Подробнее [о приоритизации в коллекции.](05-tags.md#%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)
2. Подробнее [о ключах элементов в коллекции.](05-tags.md#%D0%BA%D0%BB%D1%8E%D1%87-%D1%8D%D0%BB%D0%B5%D0%BC%D0%B5%D0%BD%D1%82%D0%B0-%D0%B2-%D0%BA%D0%BE%D0%BB%D0%BB%D0%B5%D0%BA%D1%86%D0%B8%D0%B8)

> [!IMPORTANT]
> Метод объявленный в `$priorityDefaultMethod` должен быть `public static function`
> и возвращать тип `int`, `string` или `null`.
> В качестве аргументов метод принимает два необязательных параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

> [!IMPORTANT]
> Метод объявленный в `$keyDefaultMethod` должен быть `public static function`
> и возвращать тип `string`.
> В качестве аргументов метод принимает два необязательных параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

**Пример использования хэлпер функции diTaggedAs для аргумента:**
```php
// src/Services/RuleCollection.php
namespace App\Services;

final class RuleCollection {

    public function __construct(private iterable $rules) {}
    // ...    

}
```
```php
// config/services.php
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): \Generator {
    yield diAutowire(App\Services\RuleCollection::class)
        ->bindArguments(
            rules: diTaggedAs('tags.lite-rules')
        );

    yield diAutowire(App\Rules\RuleA::class)
        ->bindTag('tags.lite-rules');

    yield diAutowire(App\Rules\RuleB::class);

    yield diAutowire(App\Rules\RuleC::class)
        ->bindTag('tags.lite-rules', priority: 100);
};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$ruleCollection = $container->get(App\Services\RuleCollection::class);
```
> [!NOTE]
> `$ruleCollection::$rules` содержит итерируемую коллекцию классов
> отсортированные по `'priority'` – `App\Rules\RuleC`, `App\Rules\RuleA`.
> Класс `App\Rules\RuleB` не попадает в коллекцию так как не отмечен
> тегом `'tags.lite-rules'`.

> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

#### diFactory

Разрешение зависимости через фабрику для классов или параметров метода (функции).

Сигнатура функции:
```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use function \Kaspi\DiContainer\diFactory;

diFactory(string|array $definition, ?bool $isSingleton = null): DiDefinitionArgumentsInterface
```

Параметры:
- `$definition` – представление php класса и метода фабрики.
- `$isSingleton` – зарегистрировать как singleton сервис. Если значение `null` то значение будет выбрано на основе [настройки контейнера](../README.md#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-dicontainer).

> [!NOTE]
> Параметр функции `$isSingleton` при применении к параметрам метода (функции) будет проигнорирован
> и не используется при разрешении зависимостей.
>

Функция `diFactory` возвращает объект реализующий интерфейс
`\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface`.

**Аргументы для фабрики:**
```php
bindArguments(mixed ...$argument)
```
> [!TIP]
> Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».

> [!NOTE]
> Подробное [описание работы с фабриками](07-factory.md) для разрешения зависимостей в контейнере.

##### Идентификатор контейнера для diFactory.
При регистрации фабрики как самостоятельного определения необходимо указывать идентификатор контейнера.

```php
// src/config/services.php
use function Kaspi\DiContainer\diFactory;

return static function (): \Generator {
    // $container->get('factories.my_factory')
    yield 'factories.my_factory' => diFactory(\App\Factories\FactoryMyClass::class);
};
```

#### diParameter
Параметр контейнера это простой скалярный тип который можно повторно использовать
для указания как разрешить зависимость.

Сигнатура функции:
```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use function \Kaspi\DiContainer\diParameter;

diParameter(string $name = ''): DiDefinitionNoArgumentsInterface
```

Параметры:
- `$name` – имя параметра контейнера.

> [!NOTE]
> Подробное [описание работы с параметрами контейнера](09-container-parameters.md).

#### diParameterRuntime
Параметр контейнера времени исполнения. [Аналогичен хелпер функции `diParameter`](#diparameter), но значение необходимо установить в контейнер
после его формирования.

Сигнатура функции:
```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use function \Kaspi\DiContainer\diParameterRuntime;

diParameterRuntime(string $name = '', ?string $message = nul): DiDefinitionNoArgumentsInterface
```

Параметры:
- `$name` – имя параметра контейнера.
- `$message` – дополнительное сообщение, если параметр контейнера еще не определен.

> [!NOTE]
> Подробное [описание работы с параметрами контейнера](09-container-parameters.md#параметры-контейнера-определяемые-во-время-выполнения).

## Получение класса по интерфейсу

### Получение через функцию обратного вызова – `\Closure`:

```php
// src/Loggers/MyLogger.php
use Psr\Log\LoggerInterface;

namespace App\Loggers;

class MyLogger {

    public function __construct(protected LoggerInterface $logger) {}
    
    public function logger(): LoggerInterface {
        return $this->logger;
    }
}
```
```php
// config/parameters.php
return [

    'logger_file' => '/path/to/your.log',

    'logger_name' => 'app-logger',

];
```
```php
// config/loggers.php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\{Logger, Handler\StreamHandler, Level};

use function Kaspi\DiContainer\{diCallable, diParameter};

return static function (): \Generator {

    yield LoggerInterface::class => diCallable(
        definition: static function (string $loggerName, string $loggerFile) {
            return (new Logger($loggerName))
                ->pushHandler(new StreamHandler($loggerFile));    
        },
        isSingleton: true
    )
        ->bindArguments(
            diParameter('logger_name'),
            diParameter('logger_file'),
        );

};
```

```php
// Создание контейнера
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters(__DIR__.'/config/parameters.php')
    ->load(__DIR__.'/config/loggers.php')
    ->build()
;

$myClass = $container->get(App\Loggers\MyLogger::class);
$myClass->logger()->debug('...');
```
> [!NOTE]
> Контейнер при разрешении зависимости `App\Loggers\MyLogger::$logger`
> по типу аргумента `Psr\Log\LoggerInterface` будет искать такой же объявленный
> идентификатор контейнера.

### Получение по интерфейсу через объявления в контейнере:

```php
// src/Classes/ClassInterface.php
namespace App\Classes;

interface ClassInterface {

    public function getFilePath(): string;

}
```
```php
// src/Classes/ClassFirst.php
namespace App\Classes;

class ClassFirst implements ClassInterface {

    public function __construct(private string $file) {}

    public function getFilePath(): string {
        return $this->file;
    }

}
```
```php
// config/services.php
use App\Classes\{ClassInterface, ClassFirst};
use function Kaspi\DiContainer\diAutowire;

return static function (): \Generator {

    yield ClassInterface::class => diAutowire(ClassFirst::class)
        // без указания именованного аргумента,
        // подставит для конструктора в параметр с индексом 0.
        ->bindArguments('/var/log/app.log')

};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

// Получение данных из контейнера с автоматическим связыванием зависимостей
$class = $container->get(App\Classes\ClassInterface::class);

print $class->getFilePath(); // /var/log/app.log
```
#### Отдельное определение для класса и привязка интерфейса к реализации для примера выше:

```php
// config/classes.php
use App\Classes\ClassFirst;
use function Kaspi\DiContainer\diAutowire;

return static function (): \Generator {

    yield diAutowire(ClassFirst::class)
        ->bindArguments(file: '/var/log/app.log')
    
};
```
```php
// config/interfaces.php
use App\Classes\{ClassFirst, ClassInterface};
use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): \Generator {
    yield ClassInterface::class => diGet(ClassFirst::class),
};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(
        __DIR__.'/config/classes.php',
        __DIR__.'/config/interfaces.php'
    )
    ->build()
;

$class = $container->get(App\Classes\ClassInterface::class);

print $class->getFilePath(); // /var/log/app.log
```

## Разрешение параметров переменной длины

> [!WARNING]
> Параметр переменной длины является опциональным и если не задан
> аргумент, то он будет пропущен при разрешении зависимости.

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
// src/Rules/RuleC.php
namespace App\Rules;

class RuleC implements RuleInterface {}
```
```php
// src/Rules/RuleGenerator.php
namespace App\Rules;

class RuleGenerator {

    private iterable $rules;

    public function __construct(RuleInterface ...$inputRule) {
        $this->rules = $inputRule;
    }
    
    public function getRules(): array {
        return $this->rules;
    }
}
```
```php
// config/services.php
use function Kaspi\DiContainer\{diAutowire, diGet};

return static function () {

    yield 'ruleC' => diAutowire(App\Rules\RuleC::class);

    yield diAutowire(App\Rules\RuleGenerator::class)
        ->bindArguments(
            diAutowire(App\Rules\RuleB::class),
            diAutowire(App\Rules\RuleA::class),
            diGet('ruleC'), // <-- получение по ссылке
        )
};
```

```php
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$ruleGenerator = $container->get(App\Rules\RuleGenerator::class);

assert($ruleGenerator->getRules()[0] instanceof App\Rules\RuleB); // true

assert($ruleGenerator->getRules()[1] instanceof App\Rules\RuleA); // true

assert($ruleGenerator->getRules()[2] instanceof App\Rules\RuleС); // true
```

> [!TIP]
> Для использования [именованных аргументов](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)
> и [параметров переменной длины](https://www.php.net/manual/ru/functions.arguments.php#functions.variable-arg-list)
> действуют правила описанные в документации php.
> 
> ```php
> // Передать три аргумента в конструктор класс
> diAutowire(App\Rules\RuleGenerator::class)
>   // Передать аргументы как именованные.
>   ->bindArguments(
>       inputRule: diAutowire(App\Rules\RuleB::class),
>
>       inputRule_2: diAutowire(App\Rules\RuleA::class),
>
>       inputRule_3: diGet('ruleC'),
>   );
> ```
> В результате в переменной `App\Rules\RuleGenerator::$inputRule` будет
> массив со значением ключей:
> ```text
> array(
>   'inputRule' => object(RuleA)#1
>   'inputRule_2' => object(RuleB)#2
>   'inputRule_3' => object(RuleC)#3
> )
> ```

## Разрешение зависимости объединенного типа.

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
// src/Services/Two.php
namespace App\Services;

use App\Classes\{One, Two};

class Service {
 
    public function __construct(
        private One|Two $dependency
    ) {}

}
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())->build();

$container->get(App\Services\Service::class);
```
> [!WARNING]
> Будет выброшено исключение `\Psr\Container\ContainerExceptionInterface`.
>

Для устранения ошибки необходимо конкретизировать тип для аргумента `$dependency`
при конфигурировании контейнера:
```php
// config/services.php
return static function (): \Generator {
    
    yield diAutowire(App\Services\Service::class)
        ->bindArguments(
            dependency: diGet(App\Classes\Two::class)
        );
  
};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$container->get(App\Services\Service::class);
```
> [!NOTE]
> При получении сервиса `App\Services\Service::class` в аргументе `App\Services\Service::$dependency`
> содержится класс `App\Classes\Two`

## Примеры использования для конфигурирования:

### Пример #1 

Один класс как самостояние определение со своими аргументами, и как реализация интерфейса, но со своими аргументами
```php
// src/Classes/SumInterface.php
namespace App\Classes;

interface SumInterface {
    public function getInit(): int;
}
```
```php
// src/Classes/Sum.php
namespace App\Classes;

class Sum implements SumInterface {

    public function __construct(private int $init) {}

    public function getInit(): int {
        return $this->init;
    }
}
```
```php
// config/services.php
use function Kaspi\DiContainer\diAutowire;

return static function (): \Generator {

    yield App\SumInterface::class => diAutowire(App\Sum::class)
        ->bindArguments(init: 50);

    yield diAutowire(App\Sum::class)
        ->bindArguments(init: 10);

};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

print $container->get(App\SumInterface::class)->getInit(); // 50

print $container->get(App\Sum::class)->getInit(); // 10
```

### Пример #2
Создание объекта без сохранения результата в контейнере.
```php
// src/Api/MyApiRequest.php
namespace App\Api;

class MyApiRequest {

    public function __construct(
         private SomeDependency $dependency,
         private string $endpoint
    ) {....}

    public function request(): string
    { 
       // .... 
    }
}
```

```php
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    // ...настройка контейнера...
    ->build();

/** @var MyApiRequest $apiV1 */
$apiV1 = (new DiDefinitionAutowire(App\Api\MyApiRequest::class))
    // SomeDependency $dependency будет разрешено контейнером
   ->bindArguments(endpoint: 'http://www.site.com/apiv1/')
   ->resolve($container);

$apiV1->request(); // выполнить запрос

/** @var MyApiRequest $apiV2 */
$apiV2 = (new DiDefinitionAutowire(App\Api\MyApiRequest::class))
    // SomeDependency $dependency будет разрешено контейнером
   ->bindArguments(endpoint: 'http://www.site.com/apiv2/')
  ->resolve($container);

$apiV2->request(); // выполнить запрос
```
- Такой вызов работает как `DiContainer::get()`, но будет каждый раз выполнять разрешение зависимостей и создание **нового объекта**;
- Подстановка аргументов для создания объекта так же может быть каждый раз разной;

### Пример #3
Заполнение коллекции на основе callback функции.
> [!NOTE]
> Похожий функционал можно реализовать [через тегированные определения](05-tags.md).
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
// src/Rules/RuleC.php
namespace App\Rules;

class RuleC implements RuleInterface {}
```
```php
// src/Services/IterableArg.php
namespace App\Services;

use App\Rules\RuleInterface;

class IterableArg
{
    /**
     * @param RuleInterface[] $rules
     */
    public function __construct(private iterable $rules) {}
}
```
```php
// config/services.php
use App\Rules\{RuleA, RuleB, RuleC}; 
use App\Services\IterableArg;
use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): \Generator {

    yield 'services.rule-list' => static fn (RuleA $a, RuleB $b, RuleC $c) => \func_get_args();
    
    // ... many definitions ...
    
    yield diAutowire(IterableArg::class)
        ->bindArguments(
            // в параметр $rules передать сервис по ссылке
            rules: diGet('services.rule-list')
        );
    
};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$class = $container->get(App\Services\IterableArg::class);
```
> [!TIP]
> Если требуется чтобы сервис `services.rule-list` был объявлен как `isSingleton`
> необходимо использовать хэлпер функцию `diCallable`
> ```php
> // config/services.php
> use App\Rules\{RuleA, RuleB, RuleC};
> use function Kaspi\DiContainer\diCallable;
>
> return static function (): \Generator {
>
>   yield 'services.rule-list' => diCallable(
>       definition: static fn (RuleA $a, RuleB $b, RuleC $c) => \func_get_args(),
>       isSingleton: true
>   );
>
>  };
> ```

### Пример #4
Использование дополнительной настройки сервиса через сеттер-методы (_mutable setter_):
```php
// config/services.php
use function Kaspi\DiContainer\diAutowire;

return static function(): \Generator {

    yield 'priority_queue.get_data' => diAutowire(\SplPriorityQueue::class)
        ->setup('setExtractFlags', [\SplPriorityQueue::EXTR_DATA]);

};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(...\glob(__DIR__.'/config/*.php'))
    ->build()
;

$priorityQueue = $container->get('priority_queue.get_data');
```
### Пример #5
Использование дополнительной настройки сервиса через сеттер-методы возвращающие новый экземпляр сервиса (_immutable setter_):
```php
// App\SomeClass.php
namespace App;

use Psr\Log\LoggerInterface;

class SomeClass {
    private LoggerInterface $logger;

    // other methods and properties.

    public function withLogger(LoggerInterface $logger): static
    {
        $new = clone $this;
        $new->logger = $logger;
    
        return $new;
    }
    
    public function getLogger(): ?LoggerInterface {
        return $this->logger ?? null;
    }
}
```
```php
// App/Services/FileLogger.php
namespace App\Services;

use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface {

    public function __construct(private string $fileName) {}
    // implement methods from LoggerInterface
}
```
Параметры контейнера:
```php
// config/parameters/params.php
return [
    'app.logger_file' => '/var/logs/application.log',
];
```
Определения для контейнера:
```php
// config/services/services.php
use function Kaspi\DiContainer\{diAutowire, diGet, diParameter};

return static function(): \Generator {
    yield diAutowire(App\Servces\FileLogger::class)
        ->bindArguments(fileName: diParameter('app.logger_file'));

    yield diAutowire(App\SomeClass::class)
        // Будет возвращён объект из метода `withLogger`
        ->setupImmutable('withLogger', [diGet(App\Servces\FileLogger::class)]);
};
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters(...\glob(__DIR__.'/config/parameters/*.php'))
    ->load(...\glob(__DIR__.'/config/services/*.php'))
    ->build()
;

$container->get(App\SomeClass::class);
```
> [!NOTE]
> При получении сервиса `App\SomeClass::class` в свойстве `App\SomeClass::$logger`
> будет класс `App\Servces\FileLogger`
