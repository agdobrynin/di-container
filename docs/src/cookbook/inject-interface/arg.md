---
outline: [2, 5]
title: Объявление внедрения в конкретный параметр
---
### Пример внедрения в конкретный параметр { #overview }

При объявлении внедрения в параметр метода или вызываемого типа[^CallableType] ожидающего объект реализующий определённый интерфейс, можно уточнить какую именно реализацию внедрять. При таком объявлении внедряемый PHP-класс может отличаться от [глобально объявленной реализации интерфейса](index.md#global).

PHP-классы:

::: code-group
<<< @/_code/cookbook/inject-interface/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/inject-interface/src/Services/Foo.php
<<< @/_code/cookbook/inject-interface/src/Services/Qux.php
<<< @/_code/cookbook/inject-interface/src/Services/Bar.php
<<< @/_code/cookbook/inject-interface/src/Services/Baz.php
:::

Необходимо объявить реализацию интерфейса `\App\Interfaces\QuxInterface` PHP-классом `\App\Services\Bar`, т.е. чтобы контейнер автоматически внедрял в параметр метода на основании конфигурации.

Для PHP-класса `\App\Services\Qux` необходимо объявить внедрение зависимости в параметр конструктора `$qux` PHP-класса `\App\Services\Baz`, обратите внимание: внедряемый класса реализуют интерфейс `\App\Interfaces\QuxInterface`.

#### Хелпер функции { #helper-fn-arg }

В файле конфигураций `services.php` указана основная реализация интерфейса `\App\Interfaces\QuxInterface` PHP-классом `\App\Services\Bar`.

::: code-group
<<< @/_code/cookbook/inject-interface/services.php
:::

Это означает что в любой параметр с типом `\App\Interfaces\QuxInterface` будет внедрён объект PHP-класса `\App\Service\Bar`.

##### diAutowire()

Файл конфигураций `services_qux.php` использует [хелпер функцию `diAutowire()`](../../documentation/php-definition/di-autowire.md) чтобы объявить внедрение в параметр конструктора `$qux` PHP-класса `\App\Services\Qux`.

::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\diAutowire;//[!code ++]

return static function (): Generator {
    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diAutowire(Baz::class)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->

---

##### diCallable()

В некоторых случая когда требуется сложная инициализация PHP-класса процесс создания такого объекта можно делегировать любому вызываемому типу[^CallableType].

Файл конфигураций `services_qux.php` использует [хелпер функцию `diCallable()`](../../documentation/php-definition/di-callable.md) чтобы объявить PHP-класс через анонимную функцию.


::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\{diAutowire, diCallable};//[!code ++]

return static function (): Generator {
    // Сложная сборка PHP-класса
    $fn = static function (): Baz {
        $baz = new Baz();
        // тут возможны дополнительные действия с $baz
        return $baz;
    };

    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diCallable($fn)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->


> [!TIP]
> Аналогичное объявление можно сделать через [хелпер функцию `diFactory()`](../../documentation/php-definition/di-factory.md).

---

##### diGet()

В случае если в контейнере уже существует объявление PHP-класса, с указанием всех необходимых внедрений и настроек, то при объявлении реализации интерфейса можно просто указать ссылку на имеющийся идентификатор контейнера этого PHP-класса.

Файл конфигураций `services_qux.php` использует [хелпер функцию `diGet()`](../../documentation/php-definition/di-get.md) для объявления внедрения в параметр.


::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\{diAutowire, diGet};//[!code ++]

return static function (): Generator {
    yield diAutowire(Baz::class);//[!code highlight]

    // … many lines

    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diGet(Baz::class)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->

#### Атрибуты { #attributes-arg }


<!--@include: ../../documentation/_include/term_notes.md-->
