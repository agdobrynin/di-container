---
outline: [2, 4]
---
#  Конфигурирование объекта в рантайм контейнер { #title }

## Обзор { #overview }

В некоторых сценариях использования контейнера зависимостей может возникнуть ситуация когда необходимо внедрить PHP класс как зависимость, но конфигурация этого PHP класса заранее не известна, т.е. на момент конфигурирования известен только идентификатор контейнера[^ContainerId] или FQCN[^FQCN] этого PHP класса.

Реальное состояние объекта (_PHP класса_) инициализируется вне контейнера, и устанавливается в контейнер зависимостей только во время выполнения, по заранее определенному идентификатору контейнера или FQCN этого PHP класса.

Такой PHP класс в рамках контейнера зависимостей называется **«Runtime Definition»**.

Объявление «**Runtime Definition**» необходимо для параметров внедрения зависящих от такого PHP класса. Во время [компиляции контейнера](../container-builder/index.md#compile-to-file) параметры должны знать о существовании PHP класса который будет установлен в контейнер зависимостей только во время выполнения.

Объявление в контейнере «**Runtime Definition**» через [хелпер функцию `diRuntime()`](../php-definition/di-runtime.md) или [атрибут `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md).

> [!IMPORTANT]
> Указание в конфигурации контейнера **«Runtime definition»** не создает конфигурацию PHP класса, а только предоставляет идентификатор контейнера[^ContainerId].


## Пример внедрения «Runtime definition» { #example-di-runtime }

### Настройка в файлах конфигураций { #example-di-runtime-php-definition }

### Конфигурация через атрибуты { #example-di-runtime-attribute }

----
🚧🚧🚧🚧

«Runtime definition» определения можно сконфигурировать через [файлы конфигураций](../00-container-builder.md#загрузка-из-файлов-конфигураций)
или указать через PHP атрибут на нужном классе.


## diRuntime
Хелпер функция для конфигурационных файлов.

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\{DiDefinitionTagArgumentInterface, DiDefinitionResetterSetterInterface};
use function \Kaspi\DiContainer\diRuntime;

diRuntime(
    string $containerIdentifierOrClass,
    ?string $message = null,
    ?string $classDefinition = null
): DiDefinitionTagArgumentInterface & DiDefinitionResetterSetterInterface
```


## Атрибут DiRuntime.
Применятся к классу для конфигурирования «Runtime definition» в контейнере.

```php
#[DiRuntime(
    string $containerIdentifier = '',
    ?string $message = null,
    array|\Kaspi\DiContainer\Attributes\Tag|null $tags = null,
    callable|false|string $resetter = false,
)]
```
Параметры:
- `$containerIdentifier` – идентификатора контейнера реализующий сервис, который будет добавлен позже.
- `$message` – дополнительное информационное сообщение при ошибке.
- `$tags` – указание тегов к конкретному идентификатору контейнера указанному в параметре `$containerIdentifier`.
- `$resetter` – значение которое будет вызвано [для сброса состояния объекта](../12-object-resetters.md).

> [!TIP]
> Атрибут может быть применен к классу несколько раз с разными значениями параметра `$containerIdentifier`.
> 

> [!TIP]
> Пустая строка в аргументе `$containerIdentifier` будет представлена как полное имя класса – **fully qualified class name** которая является идентификатором контейнера для этого php класса.

> [!NOTE]
> Значение переданное параметру `$tags` определит как будет сконфигурирован сервис:
> - значение по умолчанию `null` – конфигурировать через [атрибуты `\Kaspi\DiContainer\Attributes\Tag`](#tag) примененные к текущему классу.
> - массив из атрибутов `\Kaspi\DiContainer\Attributes\Tag` или одиночный атрибут `\Kaspi\DiContainer\Attributes\Tag` – конфигурировать теги из указанных значений.
>   - типизация параметра `list<Tag>|Tag`
> - значение пустой массив (`empty-array` aka `[]`) – не применять никаких тегов к определению.
>


> [!NOTE]
> [Пример конфигурирования через PHP атрибут](#пример-использования-php-атрибута-diruntime-для-конфигурирования).

## Пример использования хелпер функции `diRuntime` в конфигурационных файлах.

```php
// /app/config/runtime_definitions.php
use App\Core\Kernel;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diRuntime;

return static function (DefinitionsConfiguratorInterface $configurator) {
    yield diRuntime(Kernel::class);

    yield diRuntime('secure_string');
};
```
> [!NOTE]
> Другие определения в конфигурации могут «ссылаться»
> на идентификаторы контейнера `'App\\Core\\Kernel'` и `'secure_string'`
> - через [хелпер функцию `diGet()`](../03-php-definition.md#diget):
>   - `diGet(\App\Core\Kernel::class)`
>   - `diGet('secure_string')`
> - через [php атрибут `Inject`](../02-attribute-definition.md#inject):
>   - `#[Inject(\App\Core\Kernel::class)]`
>   - `#[Inject('secure_string')]`
> 

Конфигурация контейнера:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src/')
    ->load('/app/config/runtime_definitions.php')
    ->build()
;
```
Установка созданных экземпляров классов в «runtime definition»:
```php
namespace Core;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class Kernel {
    public function __construct(private DiContainerInterface $container) {}
    // ...
    protected function initKernel(): void
    {
        // ...

        $this->container->set($this::class, $this);
        
        $secureString = \calculate_secure_string();
        $this->container->set('secure_string', $secureString);
    }
}
```
## Пример использования PHP атрибута `DiRuntime` для конфигурирования.

```php
// /app/src/RuntimeServices/Foo.php
namespace App\RuntimeServices;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime]
final class Foo {
    // ...
}
```
```php
// /app/src/RuntimeServices/Bar.php
namespace App\RuntimeServices;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime('services.bar')]
final class Bar {
    // ...
}
```
Конфигурация контейнера:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src/')
    ->build()
;
```
Установка созданных экземпляров классов в «runtime definition»:
```php
namespace Core;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use App\RuntimeServices\{Foo, Bar};

class Kernel {
    public function __construct(private DiContainerInterface $container) {}
    // ...
    protected function initKernel(): void
    {
        // Инициализация класс Foo
        $foo = new Foo(...\do_calcualte_foo_params());
        $this->container->set(Foo::class, $foo);
        // Инициализация класс Bar
        $bar = new Bar(...\do_calcualte_bar_params());
        $this->container->set('services.bar', $bar);
    }
}
```
## Пример тегирования Runtime definition.
Для «runtime definition» можно определять теги.

### Пример тегирования через хелпер функцию diRuntime в конфигурационных файлах.
```php
// /app/config/runtime_definitions.php
use App\Core\Kernel;
use App\RuntimeServices\Foo;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diRuntime;

return static function (DefinitionsConfiguratorInterface $configurator) {
    yield diRuntime(Kernel::class)
        ->bindTag('tags.core');

    yield diRuntime('services.foo', classDefinition: Foo::class)
        ->bindTag(
            'tags.core_services',
            // 🚩 ключ коллекции из метода класса Foo::getKey()
            options: ['key' => 'self::getKey'],
        );
};
```
### Пример тегирования через PHP атрибуты.

```php
namespace Core;

use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;

#[DiRuntime]
#[Tag('tags.core')]
class Kernel {
    // ...
}
```
```php
// /app/src/RuntimeServices/Foo.php
namespace App\RuntimeServices;

use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;

#[DiRuntime(containerIdentifier: 'services.foo')]
#[Tag('tags.core_services', options: ['key' => 'self::getKey'])]
final class Foo {
    // ...
    public static function getKey(): string
    {
        return 'runtime_definition.foo';
    }
}
```

<!--@include: ../_include/term_notes.md-->