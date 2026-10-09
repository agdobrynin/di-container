# TaggedAs

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs` внедряет коллекцию определений контейнера отмеченных тегом.

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\TaggedAs::__construct(
    string $name,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
)
```

Параметры:
- `$name` – имя тега или FQNC [^FQCN] PHP интерфейса по которому нужно собрать коллекцию из контейнера.
- `$isLazy` – «ленивая коллекция».
- `$priorityDefaultMethod` – метод-приоритета по-умолчанию.
- `$useKeys` – использовать строковые ключи в коллекции.
- `$key` – строковый ключ элемента коллекции в мета-данных тега.
- `$keyDefaultMethod` – метод-ключа по-умолчанию.
- `$containerIdExclude` – исключить из коллекции элементы по идентификатору контейнера.
- `$selfExclude` – исключить из коллекции вызывающий PHP класс.

Внедрение зависимости применяется для параметров с типом:
- `iterable`
    - `\Traversable`
        - `\Iterator`
- `\ArrayAccess`
- `\Psr\Container\ContainerInterface`
- `array` требуется использовать параметр `$isLazy = false`.
- Составной тип (_intersection types) для ленивых коллекций (`$isLazy = true`)
    - `\ArrayAccess&\Iterator&\Psr\Container\ContainerInterface`.

> [!IMPORTANT]
> Явно не объявленные в контейнере PHP-классы будут проигнорированы при поиске тега, поэтому необходимо обеспечить доступность PHP классов в контейнере используя:
> - импорт PHP-классов – [метод `DiContainerBuilder::import()`](../container-builder/index.md#dicontainerbuilder-import)
> - файлы конфигураций - [метод `DiContainerBuilder::load()`](../container-builder/index.md#load-definitions-from-file)
>

> [!IMPORTANT]
> Метод-приоритета по-умолчанию в параметре `$priorityDefaultMethod` должен быть объявлен как `public static function` и возвращать тип `int`, `string` или `null`.
>
> В качестве параметров метод-приоритета получает два параметра:
>  - `string $tag` - имя тега.
>  - `array $options` - метаданные тега.

> [!IMPORTANT]
> Метод-ключа по-умолчанию в параметре `\Kaspi\DiContainer\Attributes\TaggedAs::$keyDefaultMethod` должен быть объявлен как `public static function` и возвращать тип `string`.
>
> В качестве параметров метод-ключа получает два параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;

> [!WARNING]
> Для внедрения зависимости в параметр с типом `array` необходимо указать `\Kaspi\DiContainer\Attributes\TaggedAs::$isLazy = false`.

> [!NOTE]
> Для подробного ознакомления с механизмом формирования **приоритетов элементов коллекции и ключами элементов коллекции** изучите раздел документации «[Работа с тегами в контейнере](../05-tags.md)».

## Внедрение ленивой коллекции по интерфейсу { #example-inject-lazy-collection-as-interface }

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
        #[TaggedAs(name: QuxInterface::class)]
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
