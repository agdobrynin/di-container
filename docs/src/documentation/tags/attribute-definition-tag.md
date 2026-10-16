# #️⃣  Настройка тегов через php атрибут { #title }

## Обзор { #overview }

[Атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) устанавливает тег и метаданные для PHP класса.

[Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) внедряет коллекцию элементов из контейнера отмеченных тегом.

<!--@include: ../_include/need_import_php_class.md-->

Конфигурирование внедрения коллекции:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.group_two')]
        public readonly iterable $collection
    ) {}

}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag; 

#[Tag('tags.group_one')]
#[Tag('tags.group_two', priority: 5)]
final class Bar
{
    // …    
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag; 

#[Tag('tags.group_two', priority: 10)]
final class Baz
{
    // …    
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

use function is_object;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->collection as $key => $item) {
    var_dump($key, is_object($item));
}
// вывод в консоли:
//string(16) "App\Services\Baz"
// bool(true)

//string(16) "App\Services\Bar"
// bool(true)
```

Порядок элементов в коллекции `\App\Services\Foo::$collection` обусловлен значением `$priority` у каждого тега. Изучите раздел документации «[Приоритет элементов коллекции](collection-priority/index.md)».

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
