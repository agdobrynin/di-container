---
outline: [2,4]
---
# Ссылки на имена других «параметров контейнера» { #title }

## Обзор

Чтобы указать ссылку на другой ранее добавленный «параметр контейнера», необходимо указать между символами `{` и `}` имя параметра.

Значение `'{emails.admin}'` будет интерпретировано как получение значения параметра контейнера с именем `'emails.admin'`.

> [!TIP]
> При объединении ссылок в строковом параметре допускается объединение числовых и строковых значений параметров.
> 
> ```php
> <?php
> 
>   return [
>       'api.host' => 'api.example.com',
>       'api.host_port' => 8080,
>       'api.url' => '{api.host}:{api.host_port}',
>   ];
> ```
> Значение параметра `'api.url'` будет приведено к строке `'api.example.com:8080'`.


## Пример использования ссылок в «параметрах контейнера» { #example }

PHP-класс:

```php
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly string $imagesPath,
        public readonly string $emails,
        public readonly array $notifyTo,
    ) {}    
}
```

Внедрить через параметры контейнера значения в конструктор PHP-класса `\App\Services\Foo`.

Файлы конфигурации параметров контейнера:

::: code-group
<<< @/_code/config/app_params.php
<<< @/_code/config/dev_params.php
:::

### Объявления внедрения в файле конфигураций { #example-php-definition }

В файле конфигураций `services.php` объявить внедрение в конструктор PHP-класса `\App\Services\Foo` через [хелпер функцию `diParameter()`](../php-definition/di-parameter.md).

В файле конфигураций параметров контейнера `app_params.php` приведены основные значения, в файле `dev_params.php` используются ссылки на другие параметры контейнера для получения нужного значения для конкретного параметра. 

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->loadParameters(// [!code highlight]
        '/app/config/app_params.php',// [!code highlight]
        '/app/config/dev_params.php',// [!code highlight]
    )// [!code highlight]
    ->load('/app/config/services.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->imagesPath);
var_dump($foo->emails);
var_dump($foo->notifyTo);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php

use App\Services\Foo;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(//[!code ++]
            imagesPath: diParameter('storage.images'),//[!code ++]
            emails: diParameter('emails.as_string'),//[!code ++]
            notifyTo: diParameter('emails.as_array'),//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/config/app_params.php
<<< @/_code/config/dev_params.php
::: 

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(19) "/var/storage/images"
string(38) "admin@example.com, manager@example.com"
array(2) {
  [0]=>
  string(17) "admin@example.com"
  [1]=>
  string(19) "manager@example.com"
}
```

### Объявление внедрения через атрибуты { #example-attribute-definition }

Через атрибут `\Kaspi\DiContainer\Attributes\Parameter` внедрить параметры контейнера в конструктор PHP-класса `\App\Services\Foo`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]

final class Foo
{
    public function __construct(
        #[Parameter('storage.images')]//[!code ++]
        public readonly string $imagesPath,
        #[Parameter('emails.as_string')]//[!code ++]
        public readonly string $emails,
        #[Parameter('emails.as_array')]//[!code ++]
        public readonly array $notifyTo,
    ) {}    
}
```
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->loadParameters(// [!code highlight]
        '/app/config/app_params.php',// [!code highlight]
        '/app/config/dev_params.php',// [!code highlight]
    )// [!code highlight]
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->imagesPath);
var_dump($foo->emails);
var_dump($foo->notifyTo);
```

<<< @/_code/config/app_params.php
<<< @/_code/config/dev_params.php
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(19) "/var/storage/images"
string(38) "admin@example.com, manager@example.com"
array(2) {
  [0]=>
  string(17) "admin@example.com"
  [1]=>
  string(19) "manager@example.com"
}
```

## Экранирование символа { { #escape-symbol }

Для экранирования в строке символа `{` необходимо его удвоить – <code v-pre>{{</code> чтобы избежать интерпретации его как ссылки на имя параметра.

Рассмотрим пример: параметр контейнера `'params.foo'` должен быть представлен строкой `'foo{bar}baz'`. Так как символ `{` является зарезервированным для синтаксиса – ссылка на имена других «параметров контейнера», его необходимо экранировать, для этого значение необходимо представить строкой <code v-pre>'foo{{bar}baz'</code>.

```php
<?php

// file: /app/config/escaped_parameters.php //[!code highlight]

return [
     'params.foo' => 'foo{{bar}baz',
];
```

