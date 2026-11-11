---
outline: [2,4]
title: Конфигурация из определений контейнера
---
### Конфигурация из определений контейнера { #title }

Объявляя PHP-класс в контейнере через хелпер функцию или атрибут можно установить значение указывающее контейнеру как именно будет выполнен сброс состояния объекта.

Можно использовать способность контейнера самостоятельно создавать конфигурацию для сброса состояния объектов на основании указанных значений в каждом объявленном PHP-классе, при этом объявлять PHP-класс `\Kaspi\DiContainer\ObjectResetters` и его конфигурацию не требуется.

Метод `reset()` предоставляемый классом `\Kaspi\DiContainer\ObjectResetters` выполнит сброс состояния объекта для каждого объявленного PHP-класса с установленным значением для сброса состояния объекта. 

При объявлении PHP-класса в [файлах конфигураций](../container-builder/configuration_files.md) для указания значения сброса состояния объекта необходимо использовать метод `setResetter()` предоставляемый хелпер функциями:
- [`diAutowire()`](../php-definition/di-autowire.md#setresetter).
- [`diRuntime()`](../php-definition/di-runtime.md#setresetter).

При объявлении PHP-класса через атрибуты необходимо передавать значение сброса состояния объекта в параметре конструктора `$resetter`:
- [атрибута `\Kaspi\DiContainer\Attributes\Autowire`](../attribute-definition/autowire.md).
- [атрибута `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md).

> [!TIP]
> Для приложений использующих **PHP 8.5 и выше** параметр конструктора `$resetter` у атрибута может быть представлен как [статическая анонимная функция](https://www.php.net/manual/ru/functions.anonymous.php#functions.anonymous):
> ```php
>  use Kaspi\DiContainer\Attributes\Autowire;
>  
>  #[Autowire(
>       isSingleton: true,
>       resetter: static function (Foo $foo): void {//[!code ++]
>           // реализация механизма сброса объекта.
>       }//[!code ++]
>  )]
>  final class Foo
>  {
>    //
>  }
> ```


### Пример конфигурирования сброса состояния объекта { #example }

PHP-классы:

::: code-group
<<< @/_code/object-resetter/src/Services/Foo.php
<<< @/_code/object-resetter/src/Services/Bar.php
<<< @/_code/object-resetter/src/Helpers/Resetters.php
<<< @/_code/object-resetter/src/Services/Baz.php
<<< @/_code/object-resetter/src/Services/Qux.php
:::

Необходимо объявить значения сброса состояния объектов в контейнере для PHP-коассов:
- `\App\Services\Foo` через метод класса `doReset()`.
- `\App\Services\Bar` через анонимную функцию в которой вызвать методы `doResetPropertyFoo()` и `doResetPropertyBar()` сбрасывающие состояние двух свойств этого класса.
- `\App\Services\Baz` через вызываемый тип `[App\Helpers\Resetters::class, 'resetObject']`.
- `\App\Services\Qux` через реализуемый метод `reset()` из интерфейса `\Kaspi\DiContainer\Interfaces\ResetInterface`.

> [!NOTE]
> Для PHP-класса `\App\Services\Qux` объявлять значение для сброса состояния объекта не обязательно, сам класс реализует интерфейс `\Kaspi\DiContainer\Interfaces\ResetInterface` и при конфигурировании PHP-класса `\Kaspi\DiContainer\ObjectResetters` контейнер самостоятельно проверить реализацию классом нужного интерфейса и включит такой класс в конфигурацию для сброса состояния объектов.

#### Объявление в файлах конфигураций { #example-php-definitin }

Рассмотрим объявление PHP-классов в файле конфигураций `services.php` с установкой сброса состояния объектов с различными допустимыми значениями через метод `setResetter()`.

```php
<?php
declare(strict_types=1);

// file: /app/src/config/services.php [!code highlight]

use App\Services\{Foo, Bar, Baz, Qux};
use App\Helpers\Resetters;

use function Kaspi\DiContainer\{diAutowire, diRuntime};

return static function (): Generator {
    yield diAutowire(Foo::class, isSingleton: true)// [!code highlight]
        ->setResetter('doReset');//[!code ++]

    yield diAutowire(Bar::class, isSingleton: true)// [!code highlight]
        ->setResetter(static function (Bar $bar): void {//[!code ++]
            $bar->doResetPropertyFoo();//[!code ++]
            $bar->doResetPropertyBar();//[!code ++]
        });//[!code ++]

    yield diAutowire(Baz::class, isSingleton: true)// [!code highlight]
        ->setResetter([Resetters::class, 'resetObject']);//[!code ++]

    yield diRuntime(Qux::class, isSingleton: true);// [!code highlight]
};
```

#### Объявление через атрибуты { #example-attribute }

Рассмотрим объявление PHP-классов через атрибуты с установкой сброса состояния объектов с различными допустимыми значениями в параметре конструктора `$resetter`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(resetter: 'doReset')]//[!code ++]
final class Foo
{
//…
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]
use App\Helpers\Resetters;//[!code ++]

#[Autowire(resetter: [Resetters::class, 'resetObject'])]//[!code ++]
final class Baz
{
//…
```

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Interfaces\ResetInterface;
use Kaspi\DiContainer\Attributes\DiRuntime;//[!code ++]

#[DiRuntime]//[!code ++]
final class Qux implements ResetInterface
{
//…
```
::: 

Для PHP-класса `\App\Services\Bar` сброс состояния объекта необходимо сделать посредством вызова двух методов класса – `doResetPropertyFoo()` и `doResetPropertyBar()`. **Если используется PHP 8.5 и выше** параметр конструктора `$resetter` у атрибута может быть представлен как статическая анонимная функция:

::: code-group
```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(//[!code ++]
    resetter: static function (Bar $bar): void {//[!code ++]
        $bar->doResetPropertyFoo();//[!code ++]
        $bar->doResetPropertyBar();//[!code ++]
    },//[!code ++]
)]//[!code ++]
final class Bar
{
//…
```
:::

**Для PHP ниже версии 8.5** можем представить параметр конструктора `$resetter` любым вызываемым типом[^CallableType], например функцией `\App\do_reset_bar()`.

::: code-group
```php [app_functions.php]
<?php
declare(strict_types=1);

// file: /app/functions/app_functions.php [!code highlight]

namespace App;

// …

use App\Services\Bar;//[!code ++]

if (!function_exists('App\do_reset_bar')) {//[!code ++]
    function do_reset_bar(Bar $bar): void {//[!code ++]
        $bar->doResetPropertyFoo();//[!code ++]
        $bar->doResetPropertyBar();//[!code ++]
    }//[!code ++]
}//[!code ++]
```
```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(resetter: 'App\do_reset_bar')]//[!code ++]
final class Bar
{
//…
```
:::

<!--@include: ../_include/term_notes.md-->