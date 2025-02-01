# 🔖 Работа с тегами в контейнере
Теги позволяют расширить возможности работы с зарегистрированными сервисами
собирая сервисы в коллекции (_списки_) и применяется для параметров с типом `iterable` и `array`.

Любое определение в контейнере может быть отмечено
одним или несколькими тегами.
Каждый тег может содержать мета-данные переданные в виде массива.

Тегирование сервисов можно произвести при объявлении в стиле [php-определений](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md)
или используя [PHP атрибуты](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md).
> ✏ При использовании тегирования через PHP атрибуты необходимо чтобы
> класс был зарегистрирован в контейнере через хэлпер функцию `diAutowire`

На каждое определение можно объявлять множество тегов.

Для получения тегированных сервисов на аргументы (_конструктор, метода или аргументы функции_) нужно использовать
- `diTaggedAs` - [функцию хэлпер](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#ditaggedas) при php определениях 
- `#[TaggedAs]` - [php атрибут](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#taggedas) 

## Объявление тега через php определение.
Для указания тегов для определения можно использовать метод:

* `bindTag(string $name, array $options)`

Метод `bindTag` доступен через [функции хэлперы](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#%D0%BE%D0%B1%D1%8A%D1%8F%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B8-%D1%85%D1%8D%D0%BB%D0%BF%D0%B5%D1%80%D1%8B)
которые реализуют интерфейсы 
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface`
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupInterface`


Пример использования с хэлпер-функцией:
```php
// определение классов
class One {}

class Two {}

class ServicesAny {
    public function __construct(private iterable $services) {}
}
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$definitions = [
    diAutowire(One::class)
        ->bindTag(name: 'tags.services-any'),
    diAutowire(Two::class)
        ->bindTag(name: 'tags.services-any'),
    diAutowire(ServicesAny::class)
        ->bindArguments(services: diTaggedAs('tags.services-any')),
];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(ServicesAny::class);
// теперь в свойстве `services` содержится итерируемая коллекция
// из классов One, Two
```
> ⚠ Если тип аргумента `array` то необходимо указать что коллекцию получить как "не ленивую":
> ```php
> diTaggedAs(name: 'tags.services-any', lazy: false)
> ```

> 📝 для параметра `$options` определено значение по умолчанию
> `['priority' => 0]` описывающее [приоритет сортировки](#приоритет-в-коллекции)
> тегированных определений.


## Объявление тега через php атрибут.

## Приоритет в коллекции.
Приоритет это положительное или отрицательное целое число,
которое по умолчанию равно 0.
**Чем больше значение приоритета, тем выше сервис будет расположен в коллекции.**

В параметре `$options` ключ `priority` является зарезервированным с помощью которого сортируются сервисы в коллекции.

Для php-определений:
```php
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;

$definitions = [
   diAutowire(App\Rules\RuleA::class)
        ->bindTag('tags.rules', ['priority' => 10]),
   //...
   diAutowire(App\Rules\RuleC::class)
        ->bindTag('tags.rules', ['priority' => 100]),
    // ...
    diAutowire(App\Rules\Rules::class)
        ->bindArguments(rules: diTaggedAs('tags.rules'))     
];
// при получении коллекции отсортированные по приоритету
// 1 - RuleC
// 2 - RuleA
```
Для php атрибута:

```php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;use Kaspi\DiContainer\Attributes\TaggedAs;

#[Tag(name: 'tags.rules', options: ['priority' => 10])]
class RuleA {}

#[Tag(name: 'tags.rules', options: ['priority' => 100])]
class RuleB {}

class Rules {
    public function __construct(
        #[TaggedAs('tags.rules')]
        private iterable $rules
    ) {}
}

$definitions = [
   diAutowire(App\Rules\RuleA::class),
   diAutowire(App\Rules\RuleC::class),
];
// при получении коллекции отсортированные по приоритету
// 1 - RuleC
// 2 - RuleA
```