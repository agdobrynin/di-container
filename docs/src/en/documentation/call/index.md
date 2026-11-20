---
outline: [2,4]
title: Container method for callable types
---
### Container method for callable types { #title }

The container implements the `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface` interface, which provides a `call()` method for callable types[^CallableType] — or for values that the container can convert into such types.

**An important feature of the `call()` method is that dependency injection into the parameters of these callable types occurs automatically, based on the container's configuration.**

The `call()` method can invoke a `callable` type, which means:
- Anonymous function.
- Function.
- Static method of a PHP class.
- Method of a PHP object.
- A PHP object that implements the [magic method `__invoke()`](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke).
- **The `call()` method can convert** an uninitialized PHP class and its method into a `callable`:
  - Invoking a non-static method of a PHP class:
    ```php
    $container->call([App\Services\Foo::class, 'methodName']);
    ```
  - Invoking the magic method `__invoke()` in a PHP class:
    ```php
    $container->call(App\Services\CallableService::class);
    ```

Method signature:

```php 
\Kaspi\DiContainer\Interfaces\DiContainerCallInterface::call(
    array|callable|string $definition,
     mixed ...$argument
)
``` 

Parameters:
- `$definition` – a callable type or a value that can be converted into a `callable` type.
- `$argument` – arguments for the callable type.

#### Arguments { #args }

The `$argument` parameter of the `call()` method passes arguments to the callable type.

When declaring dependency injection for a specific parameter of a callable type, you can use [named arguments](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments).

Parameters of the callable type that do not receive arguments will be injected by the container automatically, based on the configuration.

### Non-static method of a PHP class { #example-php-class }

Get the result of calling the `\App\Controllers\PostController::store()` method, passing an argument for the `$name` parameter.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use App\Controllers\PostController;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$result = $container->call(
    [//[!code ++]
        PostController::class,//[!code ++]
        'store'//[!code ++]
    ],//[!code ++]
    name: 'Ivan'//[!code ++]
);

var_dump($result);
```

```php [PostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/PostController.php //[!code highlight]

namespace App\Controllers;

use App\Services\ServiceOne;

use function {sprintf, var_export};

final class PostController
{
    public function __construct(private ServiceOne $serviceOne) {}
    
    public function store(string $name)
    {
        $this->serviceOne->save($name);
        
        return sprintf('The name %s saved!', var_export($name, true));
    }
}
```

<<< @/_code/src/Services/ServiceOne.php

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(22) "The name 'Ivan' saved!"
```

In effect, the `call()` method will instantiate the `\App\Controllers\PostController` class—injecting dependencies into its constructor—and then invoke the `\App\Controllers\PostController::store()` method:

```php
(new \App\Controllers\PostController(serviceOne: new ServiceOne()))
    ->post(name: 'Ivan')
```

### The __invoke() magic method in a PHP class { #example-php-class-invoke }

The PHP class `App\Controllers\SavePostController` implements the magic method `__invoke()`, which can be called via the container's `call()` method.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use App\Controllers\SavePostController;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$result = $container->call(
  SavePostController::class,//[!code ++]
  name: 'Ivan'//[!code ++]
);

var_dump($result);
```

```php [SavePostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/SavePostController.php //[!code highlight]

namespace App\Controllers;

use App\Services\ServiceOne;

use function {sprintf, var_export};

final class SavePostController
{
    // …

    public function __invoke(ServiceOne $serviceOne, string $name)
    {
        $serviceOne->save($name);
        
        return sprintf('The name %s saved!', var_export($name, true));
    }
}
```

<<< @/_code/src/Services/ServiceOne.php

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(22) "The name 'Ivan' saved!"
```

### PHP Function { #example-function }

The `\App\Functions\one_service()` function accepts two parameters: the first parameter, `$service`, is automatically injected by the container based on its configuration, while the second parameter, `$name`, must be passed via the container's `call()` method.

::: code-group


```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$result = $container->call(
  '\App\Functions\one_service',//[!code ++]
  name: 'Ivan'//[!code ++]
); 

var_dump($result);
```

```php [functions.php]
<?php
declare(strict_types=1);

// file: /app/src/Functions/functions.php //[!code highlight]

namespace App\Functions;

use App\Services\ServiceOne;

use function {sprintf, var_export};

if (!function_exists('App\Functions\one_service')) {
  function one_service(ServiceOne $service, string $name) {
          $service->save($name);
  
          return sprintf('The name %s saved!', var_export($name, true));
  }
}
```

<<< @/_code/src/Services/ServiceOne.php

:::


Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(22) "The name 'Ivan' saved!"
```

In effect, the `call()` method will execute the following code:

```php
\App\Functions\one_service(
    new App\Service\ServiceOne(),
    name: 'Ivan',
);
```

### PHP object method{ #example-object-method }

In some container usage scenarios, the `call()` method can be convenient if you need to instantiate a PHP class and then invoke one of its methods, with dependency injection into the method parameters occurring automatically based on the container's configuration.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();


$foo = new Foo();
$calculateResult = $foo->doCalculateSomething();

if (0 < $calculateResult) {
    $container->call([$foo, 'save']);
} else {
    $container->call([$foo, 'delete']);
}
```

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

final class Foo
{
    // …
    
    public function doCalculateSomething(): int
    {
        // calculate data
    }
    
    public function save(Bar $bar): void
    {
        // …
    }
    
    public function delete(Baz $baz): void
    {
        // …
    }
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

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

final class Baz
{
    // …
}
```

:::
<!--@include: ../_include/term_notes.md-->
