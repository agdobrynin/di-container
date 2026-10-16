# #️⃣  Настройка тегов через php атрибут { #title }

## Обзор { #overview }

[Атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) устанавливает тег и метаданные для PHP класса.

[Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) внедряет коллекцию элементов из контейнера отмеченных тегом.

<!--@include: ../_include/need_import_php_class.md-->

## Пример конфигурирования через атрибут { #example-inject-by-attribute-tagged-as }

Необходимо внедрить в параметр `\App\Services\RuleCollection::$rules` «ленивую коллекцию» из PHP классов отмеченных тегом `'tags.lite-rules'`.

::: code-group
```php [RuleCollection.php]
// file: /app/src/Services/RuleCollection.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class RuleCollection
{
    public function __construct(
        #[TaggedAs('tags.lite-rules')]
        public readonly iterable $rules
    ) {}
}
```

```php [RuleA.php]
// file: /app/src/Rules/RuleA.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.lite-rules')]
final class RuleA
{
    // …
}
```

```php [RuleB.php]
// file: /app/src/Rules/RuleB.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.other-rulers')]// [!code warning]
final class RuleB
{
    // …
}
```

```php [RuleC.php]
// file: /app/src/Rules/RuleC.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.lite-rules', priority: 100)]
final class RuleC
{
    // …
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\RuleCollection;


$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

$ruleCollection = $container->get(RuleCollection::class);

foreach ($ruleCollection->rules as $rule) {
    var_dump($rule::class);
}

// вывод консоли:
// string (15) "App\Rules\RuleC"
// string (15) "App\Rules\RuleA"
```

Свойство `\App\Services\RuleCollection::$rules` содержит коллекцию классов отсортированную по значению в параметре `\Kaspi\DiContainer\Attributes\Tag::$priority`.

Класс `\App\Rules\RuleB` не попадает в коллекцию, так как не отмечен тегом `'tags.lite-rules'`.

## Внедрение зависимости в параметр с типом `array` { #inject-by-attribute-tagged-as-array-param }

При внедрении коллекции элементов из контейнера отмеченных тегом в параметр с типом `array` обязательно передать значение `false` для параметра `$isLazy` в [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

class Qux
{
    public function __construct(
        #[TaggedAs(
            'tags.lite-rules',
            isLazy: false,// [!code ++]
        )]
        public readonly array $collection// [!code highlight]
    ) {}

}
```

## Внедрение коллекции по интерфейсу { #example-inject-collection-as-interface }

В качестве имени тега можно использовать FQCN [^FQCN] PHP интерфейса. Указав в качестве тега имя интерфейса контейнер найдет все доступные ему PHP классы реализующий указанный интерфейс и вернёт коллекцию.

Собрать коллекцию из PHP классов которые реализуют интерфейс `\App\Interfaces\QuxInterface`:

Конфигурация PHP классов:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;
use App\Interfaces\QuxInterface;

final class Foo
{
    public function __construct(
        /*
         * 🚩 Коллекция с ленивой инициализацией
         */
        #[TaggedAs(QuxInterface::class)]
        public readonly iterable $services
    ) {}
}
```

```php [QuxInterface.php]
// file: /app/src/Interfaces/QuxInterface.php
namespace App\Interfaces;

interface  QuxInterface
{    
    // …
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

```php [Bat.php]
// file: /app/src/Services/Bat.php
namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bat implements QuxInterface
{
    // …
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespae: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}

// string(16) "App\Services\Bar"
// string(16) "App\Services\Bat"
```

PHP класс `\App\Services\Baz` не реализует интерфейс `\App\Interfaces\QuxInterface` поэтому его нет в коллекции `$foo->services`.

<!--@include: ../_include/term_notes.md-->

## Внедрение коллекции для параметров переменной длины { #inject-by-attribute-tagged-as-variadic }

Параметр переменной длины так же поддерживает внедрение коллекций элементов из контейнера отмеченных тегом.

```php
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Qux
{
    public function __construct(
        #[TaggedAs('tags.group_one')]
        #[TaggedAs('tags.group_two')]
        iterable ...$collection
    ) {}

}
```

> [!TIP]
> <!--@include: ../_include/variadic_params.md-->
