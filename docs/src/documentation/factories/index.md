---
outline: [2,4]
title: Использование фабричных методов для внедрения зависимостей
---
### Использование фабричных методов для внедрения зависимостей { #title }

В некоторых сценариях возникает необходимость для внедрения зависимостей применить шаблон проектирования «фабрика» чтобы делегировать процесс создания требуемого объекта специальному PHP-классу – «фабрика». В таких случаях контейнер может вызвать метод вашего PHP-класса для внедрения зависимости.

<!--@include: _include/auto_inject_params.md-->

При объявлении внедрения зависимости через фабрику выбранное значение для класса не влияет на получаемый результат выполнения фабричного метода. [Фактическое имя класса фабрики](factory_class_container_id.md) зависит только от объекта получаемого через метод контейнера `get()`. Однако разумное значение для имени класса фабрики может упростить его использование при конфигурировании контейнера.

**В качестве PHP-класса «фабрика» можно использовать:**
- Публичный статический метод PHP-класса.
- Публичный метод PHP-класса.
- Магический метод `__invoke()` реализованный в PHP-классе.

PHP-класс «фабрика» можно использовать для [объявления внедрения в параметры](inject_into_params.md) методов или вызываемых типов[^CallableType].

PHP-класс «фабрика» можно использовать для [объявления PHP-класса](declare_class.md) когда простой вызов `new Foo()` невозможен, и требуется особая сборка объекта.

Для вызова «фабрики» следует указывать значение в параметре `$definition` [хелпер функции `diFactory()`](../php-definition/di-factory.md) в файлах конфигураций, или [атрибута `\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md).

### Содержание раздела { #toc }

- [Аргументы для метода фабрики](args.md)
- [Объявление PHP-класса через фабрику](declare_class.md)
- [Объявление внедрения зависимости в параметры](inject_into_params.md)
- [Фактическое имя класса фабрики](factory_class_container_id.md)

### Примеры объявления PHP-класса «фабрика» { #example }

Рассмотрим общий и короткий пример объявления PHP-класса в контейнере через PHP-класс «фабрика».

#### Фабрика со статическим методом { #example-static-method }

Статический метод фабрики является вызываемым типом[^CallableType], т.е. сам объект PHP-класса фабрики не будет создан контейнером, результат вызова статического метода и будет реализацией объявляемого класса `\App\Services\Foo`.

PHP-класс «фабрика»:

```php
<?php
declare(strict_types=1);

namespace App\Factories;

use App\Services\{Foo, Bar};

final class Factory
{
    public static function createFoo(Bar $bar): Foo
    {
        $createdObject = new Foo($bar));

        // additional configuration for $createdObject

        return $createdObject;
    }
}
```

Объявление через хелпер функцию:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use App\Factories\Factory;

use function Kaspi\DiContainer\diFactory;//[!code highlight]

return static function () {
    yield Foo::class => diFactory(//[!code highlight]
        definition: [//[!code ++]
            Factory::class,//[!code ++]
            'createFoo',//[!code ++]
        ]//[!code ++]
    );//[!code highlight]
};
```

Объявление через атрибут:

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code highlight]
use App\Factories\Factory;

#[DiFactory(//[!code highlight]
    definition: [//[!code ++]
        Factory::class,//[!code ++]
        'createFoo',//[!code ++]
    ]//[!code ++]
)]//[!code highlight]
class Foo
{
    // …
}
```

#### Фабрика с нестатическим методом { #example-method }

Если ваша фабрика использует обычный метод вместо статического, первым шагом необходимо убедиться что PHP-класс самой фабрики также был объявлен и доступен в контейнере, т.е. может быть получен через метод контейнера `get(App\Factories\Factory::class)`.

PHP-класс «фабрика»:

```php
namespace App\Factories;

use App\Services\{Foo, Bar};

final class Factory
{
    public function __construct(private Bar $bar) {}

    public function createFoo(): Foo
    {
        $createdObject = new Foo($this->bar);

        // additional configuration for $createdObject
        
        return $createdObject;
    }
}
```

Объявление через хелпер функцию:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use App\Factories\Factory;

use function Kaspi\DiContainer\diFactory;//[!code highlight]

return static function () {
    yield Foo::class => diFactory(//[!code highlight]
        definition: [//[!code ++]
            Factory::class,//[!code ++]
            'createFoo',//[!code ++]
        ]//[!code ++]
    );//[!code highlight]
};
```

Объявление через атрибут:

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code highlight]
use App\Factories\Factory;

#[DiFactory(//[!code highlight]
    definition: [//[!code ++]
        Factory::class,//[!code ++]
        'createFoo'//[!code ++]
    ]//[!code ++]
)]//[!code highlight]
class Foo
{
    // …
}
```

#### Фабрика реализующая магический метод __invoke() { #example-invoke }

Если ваша фабрика реализует [магический метод `__invoke()`](https://www.php.net/manual/ru/language.oop5.magic.php#object.invoke), первым шагом необходимо убедиться что PHP-класс самой фабрики также был объявлен и доступен в контейнере, т.е. может быть получен через метод контейнера `get(App\Factories\FooFactory::class)`.

Синтаксис объявления фабрики может быть сокращён до указания FQCN[^FQCN].

PHP-класс «фабрика»:

```php
namespace App\Factories;

use App\Services\{Foo, Bar};

final class FooFactory
{
    public function __construct(private Bar $bar) {}

    public function __invoke(): Foo
    {
        $createdObject = new Foo($this->bar);

        // additional configuration for $createdObject
        
        return $createdObject;
    }
}
```

Объявление через хелпер функцию:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use App\Factories\FooFactory;

use function Kaspi\DiContainer\diFactory;

return static function () {
    yield Foo::class => diFactory(FooFactory::class);//[!code ++]
};
```

Объявление через атрибут:

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use App\Factories\FooFactory;//[!code highlight]

#[DiFactory(FooFactory::class)]//[!code ++]
class Foo
{
    // …
}
```

<!--@include: ../_include/term_notes.md-->