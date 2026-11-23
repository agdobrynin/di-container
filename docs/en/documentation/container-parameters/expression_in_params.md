---
outline: [2,4]
title: References to the names of other container parameters
---
### References to the names of other container parameters { #title }

To reference another previously added container parameter, you must specify the parameter name between the `{` and `}` characters.

The value `'{emails.admin}'` will be interpreted as a request to retrieve the value of the container parameter named `'emails.admin'`.

> [!TIP]
> When combining links within a string parameter, it is permissible to include both numeric and string parameter values.
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


### An example of using links in container parameters { #example }

PHP classes:

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

Inject values ​​into the constructor of the PHP class `\App\Services\Foo` via container parameters.

Container parameter configuration files:

::: code-group
<<< @/_code/config/app_params.php
<<< @/_code/config/dev_params.php
:::

#### Declarations of implementation in the configuration file { #example-php-definition }

In the `services.php` configuration file, declare an injection into the constructor of the PHP class `\App\Services\Foo` using the [`diParameter()`](../php-definition/di-parameter.md) helper function.

The `app_params.php` configuration file contains the primary parameter values for the container, while the `dev_params.php` file uses references to other container parameters to derive the required values for specific parameters.

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

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

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

#### Declaring implementation via attributes { #example-attribute-definition }

Inject container parameters into the constructor of the PHP class `\App\Services\Foo` using the `\Kaspi\DiContainer\Attributes\Parameter` attribute.

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

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

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

### Character escaping { { #escape-symbol }

To escape the `{` character within a string, you must double it <code v-pre>{{</code> to prevent it from being interpreted as a reference to a parameter name.

Consider this example: a container parameter `'params.foo'` needs to be represented by the string `'foo{bar}baz'`. Since the `{` character is reserved for syntax—specifically for referencing other container parameters it must be escaped; therefore, the value should be written as <code v-pre>'foo{{bar}baz'</code>.

```php
<?php

// file: /app/config/escaped_parameters.php //[!code highlight]

return [
     'params.foo' => 'foo{{bar}baz',
];
```

