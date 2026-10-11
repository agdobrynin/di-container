# 🐘 Конфигурирование тегов в стиле php определений { #title }

## Обзор { #overview }

Для указания тегов используется метод `bindTag()` доступный в хелпер функциях [`diAutowire()`](../php-definition/di-autowire.md#bindtag),
[`diAutowire()`](../php-definition/di-autowire.md#bindtag), [`diCallable()`](../php-definition/di-callable.md#bindtag), [`diProxyClosure()`](../php-definition/di-proxy-closure.md#bindtag), [`diValue()`](../php-definition/di-value.md#bindtag).

Метод `bindTag()` описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`.

Сигнатура метода:

```php
DiDefinitionTagArgumentInterface::bindTag(
    string $name,
    array $options = [],
    null|int|string $priority = null
)
```

Параметры:
- `$name` – имя тега.
- `$options` – мета-данные для тега.
- `$priority` – [приоритет элемента в коллекции](collection-priority.md).

> [!TIP]
> Ознакомьтесь с разделом «[Рекомендации по именованию значений для имён тегов, и мета-данных для тега](naming-convention.md)».

<!--@include: _include/tag_options.md-->


----
🚧🚧🚧🚧🚧

🧪 Пример использования с хелпер функцией `diAutowire`:
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
// src/Services/TaggedServices.php
namespace App\Services;

class TaggedServices {

    public function __construct(private iterable $services) {}

}
```
```php
// config/services.php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): \Generator {

    yield diAutowire(App\Classes\One::class)
        ->bindTag(name: 'tags.service_any');

    yield diAutowire(App\Classes\Two::class)
        ->bindTag(name: 'tags.service_any');

    yield diAutowire(App\Services\TaggedServices::class)
        ->bindArguments(services: diTaggedAs('tags.service_any'));

};
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$class = $container->get(App\Services\TaggedServices::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Services\TaggedServices::class`
> в свойстве `App\Services\TaggedServices::$services` содержится итерируемая «ленивая» коллекция
> из классов `App\Classes\One`, `App\Classes\Two`.

> [!TIP]
> Если тип аргумента куда добавляется тегированная коллекция `array`
> то необходимо указать "не ленивое" получение:
> ```php
>   use function Kaspi\DiContainer\diTaggedAs;
> 
>   diTaggedAs(tag: 'tags.service_any', isLazy: false)
> ```

#### Получение тегированных сервисов можно применять так же **параметрам переменной длинны**:

> [!WARNING]
> Параметр переменной длины является опциональным и если у него не задан
> аргумент указывающий как разрешать зависимость, то параметр будет пропущен.

```php
// src/Services/TaggedServices.php
namespace App\Services;

class TaggedServices {

    public function __construct(
        array ...$srvGroup
    ) {}

}
```
```php
// config/services.php
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): \Generator { 

    yield diAutowire(namespace App\Classes\One::class)
        ->bindTag(name: 'tags.group_1');

    yield diAutowire(App\Classes\Two::class)
        ->bindTag(name: 'tags.group_1');

    yield diAutowire(App\Classes\Three::class)
        ->bindTag(name: 'tags.group_2');

    yield diAutowire(App\Classes\Four::class)
        ->bindTag(name: 'tags.group_2');

    yield diAutowire(App\Services\TaggedServices::class)
        ->bindArguments(
            // аргумент имеет тип array то $isLazy=false
            diTaggedAs('tags.group_1', false),
            diTaggedAs('tags.group_2', false),
        );

};
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$class = $container->get(App\Services\TaggedServices::class);
```
> [!NOTE]
> При разрешении параметров конструктора класса `App\Services\TaggedServices::class`
> в свойстве `App\Services\TaggedServices::$srvGroup[0]` содержится массив из классов `App\Classes\One`, `App\Classes\Two`,
> а в свойстве `App\Services\TaggedServices::$srvGroup[1]` массив из классов `App\Classes\Three`, `App\Classes\Four`.

> [!TIP]
> Для использования [именованных аргументов](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)
> и [параметров переменной длины](https://www.php.net/manual/ru/functions.arguments.php#functions.variable-arg-list)
> действуют правила описанные в документации php.

Передать именованные аргументы для сервиса к параметру переменной длины:
```php
use function Kaspi\DiContainer\diTaggedAs;

return static function (): \Generator
    //...
    yield diAutowire(App\Services\TaggedServices::class)
        ->bindArguments(
            // аргумент имеет тип array то $isLazy=false
            srvGroup: diTaggedAs('tags.group_1', false),
            srvGroup_2: diTaggedAs('tags.group_2', false),
        );
};
```
> [!NOTE]
> При разрешении параметров конструктора класса `App\Services\TaggedServices::class`
> в `App\Services\TaggedServices::$srvGroup` содержится массив
> со строковыми ключами `srvGroup` и `srvGroup_2` – как переданные именование аргументы.