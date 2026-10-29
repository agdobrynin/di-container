---
outline: [2, 4]
---
# Исключение элементов из коллекции { #title }

## Обзор { #overview }

Коллекции могу исключать элементы. Исключить из коллекции можно вызывающий PHP класс т.е. самого себя, или по идентификатору контейнера [^ContainerId] элемента коллекции.

## Исключение вызывающего класса { #exclide-self }

В коллекции элементов может содержаться PHP класс внедряющий эту же коллекцию. Для исключения такого поведения обратите внимание на параметр `$selfExclude`
в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) и [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md). По умолчанию значение этого параметра `true`.

Если вызывающий PHP класс должен быть в коллекции, то необходимо указать в параметр `$selfExclude` значение `false`.

::: code-group

```php [хелпер функция diTaggedAs()]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\ClassTaggedAs;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(ClassTaggedAs::class)
        ->bindTag('tags.foo')//[!code highlight]
        ->bindArguments(
            items: diTaggedAs(
                'tags.foo',//[!code highlight]
                selfExclude: false,//[!code ++]
            )
        );
};
```

```php [атрибут TaggedAs]
<?php
declare(strict_types=1);

// file: /app/src/Services/ClassTaggedAs.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\{Tag, TaggedAs}; 

#[Tag('tags.foo')]//[!code highlight]
final class ClassTaggedAs
{
    public function __construct(
        #[TaggedAs(
            'tags.foo',//[!code highlight]
            selfExclude: false,//[!code ++]
        )]
        public readonly iterable $items
    ) {}
}
```

:::

## Исключение из коллекции по идентификатору контейнера { #exclude-by-id }

Для исключения элементов из коллекции используйте параметр `$containerIdExclude`
в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) и [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

Параметр `$containerIdExclude` может содержать идентификаторы контейнера [^ContainerId] исключаемых из внедряемой коллекции.

### Пример исключения из коллекции элементов { #example-exclude-by-id }

PHP классы `\App\Service\Bar`, `\App\Service\Baz`, `\App\Service\Qux` отмечены тегом `'tags.internal_service'`.

В параметр `\App\Service\Foo::$services` необходимо внедрить коллекцию элементов отмеченных тегом `'tags.internal_service'`, но исключить из коллекции элемент с идентификатором контейнера `'App\Services\Baz'`, т.е. сервис получаемый через метод контейнера `$container->get(App\Services\Baz::class)`.

#### Объявление в файлах конфигураций { #example-exclude-by-id-php-definitions }

[В файле конфигураций](../container-builder/configuration_files.md) в параметре `$containerIdExclude` хелпер функции `diTaggedAs()` указать идентификатор `'App\Services\Baz'` для исключения из коллекции.


::: code-group


```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\{Foo, Bar, Baz, Qux};

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            services: diTaggedAs(
                tag: 'tags.internal_service',//[!code highlight]
                containerIdExclude: [//[!code ++]
                    Baz::class,//[!code ++]
                ],//[!code ++]
            )
        );
        
    yield diAutowire(Bar::class)
        ->bindTag('tags.internal_service');//[!code highlight]
        
    yield diAutowire(Baz::class)
        ->bindTag('tags.internal_service');//[!code highlight]
        
    yield diAutowire(Qux::class)
        ->bindTag('tags.internal_service');//[!code highlight]
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
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}

// console output:
// string(16) "App\Services\Bar"
// string(16) "App\Services\Qux"
```

<<< @/_code/Tags/src/Services/Foo.php

<<< @/_code/Tags/src/Services/Bar.php

<<< @/_code/Tags/src/Services/Baz.php

<<< @/_code/Tags/src/Services/Qux.php

:::

#### Объявление через атрибуты { #example-exclude-by-id-php-attributes }

В параметре `$containerIdExclude` [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) указать идентификатор `'App\Services\Baz'` для исключения из коллекции.

Объявление тега PHP классу через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md). 

::: code-group

<<< @/_code/Tags/src/Services/FooAttr.php [Foo.php]

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}

// console output:
// string(16) "App\Services\Bar"
// string(16) "App\Services\Qux"
```

<<< @/_code/Tags/src/Services/BarAttr.php [Bar.php]

<<< @/_code/Tags/src/Services/BazAttr.php [Baz.php]

<<< @/_code/Tags/src/Services/QuxAttr.php [Qux.php]

:::

<!--@include: ../_include/term_notes.md-->
