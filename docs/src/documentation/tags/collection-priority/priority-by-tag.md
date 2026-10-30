---
outline: [2, 4]
---
# Приоритет тега { #priority-by-tag }

## Обзор { #overview }

Приоритет тега указывается в параметре `$priority` метода `bindTag()`[^BindTag], или [атрибута `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

## Пример конфигурации { #example-priority-by-tag }

Необходимо внедрить коллекцию в определенном порядке в параметр `$services` через конструктор класса `\App\Services\Foo` используя **приоритет тега**:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

::: code-group

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

:::


### Объявление в файлах конфигураций { #priority-by-tag-php-definition }

В [файлах конфигураций](../../container-builder/configuration_files.md) необходимо указать приоритет каждого тега в параметре `$priority` метода `bindTag()`[^BindTag].

::: code-group

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\Foo;

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.ordered')//[!code highlight]
        );
};
```

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php //[!code highlight]

use App\Services\{Bar, Baz, Qux};

use function \Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag('tags.ordered');//[!code highlight]
   
   yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            priority: 10,//[!code ++]
        );

   yield diAutowire(Qux::class)
        ->bindTag(
            name: 'tags.ordered',//[!code highlight]
            priority: 100,//[!code ++]
        );
};
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/tagged_services.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}

// console output:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```
<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

::: 

Порядок сортировки внедрённой коллекции обусловлен значениями `$prioriy` для каждого тега.

### Объявление через атрибуты { #priority-by-tag-attributes }

Элементам коллекции, представленные PHP классами, добавим [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) с параметром `$priority`.

::: code-group

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code highlight]

#[Tag('tags.ordered')]// [!code highlight]
final class Bar
{
    // …
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code highlight]

#[Tag(// [!code highlight]
    'tags.ordered',// [!code highlight]
    priority: 10,// [!code ++]
)]// [!code highlight]
final class Baz
{
    // …
}
```

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php // [!code highlight]
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code highlight]

#[Tag(// [!code highlight]
    'tags.ordered',// [!code highlight]
    priority: 100,// [!code ++]
)]// [!code highlight]
final class Qux
{
    // …
}
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}

// console output:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"

```

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code highlight]

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.ordered')]// [!code highlight]
        public readonly iterable $services
    ) {}    
}
```

:::

Порядок сортировки внедрённой коллекции обусловлен значениями в параметре `$priority` атрибута `\Kaspi\DiContainer\Attributes\Tag` для каждого тега.

<!--@include: ../../php-definition/_include/bind_notes.md-->
