---
outline: [2,4]
---
# Фактическое имя класса фабрики { #title }

## Обзор { #overview }

Обычно PHP-класс «фабрика»[^FactoryPattern] представлен двумя строковыми значениями – имя PHP-класса и имя метода этого класса:

```php
$factoryDefinition = [App\Services\Foo::class, 'methodName'];
```

Для получения результата вызова PHP-класса «фабрика» первым этапом будет **получен объект** через метод контейнера `get('App\Services\Foo')`, и только потом происходит **вызов фабричного метода** `methodName()`. 

Из этого следует, что строковое значение представляющее имя PHP-класса, является идентификатором контейнера[^ContainerId].

Указание в качестве имени класса фабрики допустимо любого идентификатора контейнера, если получаемый из контейнера объект реализует фабричный метод.

## Пример указания идентификатора контейнера в качестве имени класс фабрики { #example }

Через идентификатор контейнера `'services.foo_special'` можно получить настроенный особым образом объект PHP-класса `\App\Services\Foo`.

Необходимо объявить внедрение зависимости через фабрику `\App\Services\Foo::doBaz()` в параметр конструктора `$value` PHP-класса `\App\Services\Bar`:

```php
<?php
declare(strict_types=1);

namespace App\Services;

final class Bar
{
    public function __construct(public readonly mixed $value) {}    
}
```

Файл конфигураций `custom_services.php` задает особую конфигурацию PHP-класса `\App\Services\Foo` и идентификатор контейнера `'services.foo_special'`:

```php
<?php
declare(strict_types=1);

// file: /app/config/custom_services.php

use App\Services\Foo;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield 'services.foo_special' => diAutowire(Foo::class, isSingleton: true)
        ->setup('methodOne')
        ->setup('methodTwo');
};
```

### Объявление в файлах конфигураций { #example-php-definition }

В файле конфигураций `services.php` объявить PHP-класс `\App\Services\Bar` в контейнере и внедрение зависимости для параметра конструктора `$value` с помощью [хелпер функции diFactory()](../php-definition/di-factory.md).

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php  // [!code highlight]

use App\Service\Bar;

use function Kaspi\DiContainer\{diAutowire, diFactory};

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindArguments(
            value: diFactory(['services.foo_special', 'doBaz'])//[!code ++]        
        );
};
```

### Объявление через атрибут { #example-attribute }

Объявить внедрение в параметр конструктора `$value` PHP-класса `\App\Services\Bar` через [атрибут `\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md).

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]

final class Bar
{
    public function __construct(
        #[DiFactory(['services.foo_special', 'doBaz'])]//[!code ++]
        public readonly mixed $value
    ) {}    
}
```

<!--@include: ../_include/term_notes.md-->
