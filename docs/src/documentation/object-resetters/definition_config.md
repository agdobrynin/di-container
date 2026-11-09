---
outline: [2,4]
---
# Конфигурация из определений контейнера { #title }

## Обзор { #overview }

Объявляя PHP-класс в контейнере через хелпер функцию или атрибут можно установить значение указывающее контейнеру как именно будет выполнен сброс состояния объекта.

Объявлять PHP-класс `\Kaspi\DiContainer\ObjectResetters` и его конфигурацию не требуется, контейнер самостоятельно создаст конфигурацию для сброса состояния объектов и объявит PHP-класс `\Kaspi\DiContainer\ObjectResetters`.

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


## Пример конфигурирования сброса состояния объекта { #example }

PHP-классы:

::: code-group
<<< @/_code/object-resetter/src/Services/Foo.php
<<< @/_code/object-resetter/src/Services/Bar.php
<<< @/_code/object-resetter/src/Helpers/Resetters.php
<<< @/_code/object-resetter/src/Services/Baz.php
<<< @/_code/object-resetter/src/Services/Qux.php
:::

### Объявление в файлах конфигураций { #example-php-definitin }

Рассмотрим объявление PHP-классов в файле конфигураций `services.php` с установкой сброса состояния объектов с различными допустимыми значениями.

```php
<?php
declare(strict_types=1);

// file: /app/src/config/services.php [!code highlight]

use App\Services\{Foo, Bar, Baz};
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

    yield diRuntime(Qux::class, isSingleton: true)// [!code highlight]
        ->setResetter(false);// default value [!code warning]
};
```

### Объявление через атрибуты { #example-attribute }

