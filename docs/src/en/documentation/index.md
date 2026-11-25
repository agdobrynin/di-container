---
outline: [2,4]
title: Manual. Introduction.
---
### Introduction { #introduction }

[Dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) is an important design pattern in software development. The core idea is that classes do not create the objects they need; instead, they receive them from an external source. This ensures code independence and makes the code more reusable and easier to test.

**Dependency injection** is a technique for writing better-quality code.

A **dependency injection container** is a tool that helps automatically assemble objects and inject their dependencies based on configuration rules or autowiring.

Let’s look at a simplified example using a set of PHP classes. These classes illustrate the classic pattern of passing dependencies via a constructor:

::: code-group
```php [PostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/PostController.php [!code highlight]

namespace App\Controllers;

use App\Services\Mail;
use App\Models\Post;

// Controller for processing an action
final class  PostController
{
    public function __construct(private Mail $mail) {}
    // …
}
```
```php [Mail.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Mail.php [!code highlight]

namespace App\Services;

// Email sending service
final class Mail
{
    public function __construct(private Envelope $envelope) {}
    // …
}
```
```php [Envelope.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Envelope.php [!code highlight]

namespace App\Services;

// Class for creating a message
final class Envelope
{
    // ...
}
```
:::

Now, let’s imagine that to obtain an instance of the `\App\Controllers\PostController` class, we need to pass an instance of the `App\Services\Mail` class into its constructor – a class which, in turn, depends on an instance of the `\App\Services\Envelope` class.

This results in a chain of object creation and dependency passing via the constructor:

```php
<?php
declare(strict_types=1);

// file: /app/src/index.php [!code highlight]

require_once __DIR__ . '/../vendor/autoload.php';// [!code warning]

$envelop = new \App\Services\Envelope();
$mail = new \App\Services\Mail($envelop);
$controller = new \App\Controllers\PostController($mail);
```

A dependency injection container is designed to make dependency injection more practical and to automate dependency injection operations based on a provided configuration.

---

### DiContainer overview { #overview }

Kaspi/di-container is a dependency injection container for PHP >= 8.1 that implements the [PSR-11](https://www.php-fig.org/psr/psr-11/).

#### Features { #features }

- **Autowiring**: automatic dependency injection — you declare dependencies using type hints, and the container handles the rest. In most cases, you do not need to define object configuration;
- **Lazy injection**: allows for the deferred creation of object dependencies until the moment they are actually used;
- **Attribute-driven**: declarative dependency injection using native PHP 8+ attributes;
- **Zero configuration**: no object configuration is required if a PHP class has no dependencies or depends only on other concrete classes;
- **Definition Tags**: allows for the injection or retrieval of a collection of items from the container that share the same tag name;
- **Compiling the Container**: generates a configured container as high-performance PHP code, optimized specifically for your configuration and classes;
- **PSR-11**: compliant with the PSR-11 standard for dependency injection containers;
- **Object state reset**: a mechanism for resetting the state of objects within long-running processes;

### Install { #install }

Use the PHP dependency manager [composer](https://getcomposer.org/) to install the "kaspi/di-container" package.

```shell
composer require kaspi/di-container
```

---

### Quck start { #quick-start }

Let’s look at a simplified example involving a set of PHP classes for which we need to implement automatic dependency injection using the "kaspi/di-container" dependency injection container.

PHP classes:

::: code-group

```php [PostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/PostController.php [!code highlight]

namespace App\Controllers;

use App\Services\Mail;
use App\Models\Post;

// Controller for processing an action
final class  PostController
{
    public function __construct(private Mail $mail) {}
    
    public function send(Post $post): bool
    {
        $this->mail->envelop()
            ->subject('Publication success')
            ->message('Post <'.$post->title.'> was published.');

        return $this->mail->send();
    }
}
```

```php [Mail.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Mail.php [!code highlight]

namespace App\Services;

// Email sending service
final class Mail
{
    public function __construct(private Envelope $envelope) {}
    
    public function envelop(): Envelope
    {
        return $this->envelope;
    }
    
    public function send(): bool
    {
        // sending message
        return true;
    }
}
```

```php [Envelope.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Envelope.php [!code highlight]

namespace App\Services;

// Class for creating a message
final class Envelope
{
    public function subject(string $subject): void
    {
        // ...
    }

    public function message(string $message): void
    {
        // ...
    }
}
```
```php [Post.php]
<?php
declare(strict_types=1);

// file: /app/src/Models/Post.php [!code highlight]

namespace App\Models;

// Data model — blog post
class Post
{
    public string $title;
    // ...
}
```

:::

In action:

::: code-group
```php [in the style of PSR-11]
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use App\Controllers\PostController;
use App\Models\Post;

$container = (new DiContainerBuilder())
    ->build();

if ($container->has(PostController::class)) {
    // An object with dependencies injected via the constructor[!code warning]
    $postController = $container->get(PostController::class);
    
    // let's fill in the post details[!code warning]
    $post = new Post();
    $post->title = 'Lorem ipsum';

    // calling a method on the obtained object[!code warning]
    $postController->send($post);
}
```
```php [container's call() method]
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use App\Controllers\PostController;
use App\Models\Post;

$container = (new DiContainerBuilder())
    ->build();

// let's fill in the post details[!code warning]
$post = new Post();
$post->title = 'Lorem ipsum';

$container->call([PostController::class, 'send'], $post);
```
:::

---

### PSR-11

The container is fully compatible with the [PSR-11](https://www.php-fig.org/psr/psr-11/) recommendations.

```php
<?php
declare(strict_types=1);

namespace Psr\Container;

interface ContainerInterface
{
    public function get($id);
 
    public function has($id);
}
```
### `call()`

The container implements the `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface` interface, which provides the call() method for callable types or values that can be converted by the container to the callable type.

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use Kaspi\DiContainer\DiContainerBuilder;

use function var_export;

$container = (new DiContainerBuilder())->build();

$container->call(
    static function(Foo $foo, string $name): void {
        $foo->doSomethind($name);
        sprintf('Call Foo::doSomething(%s)', var_export($name, true));
    },
    name: 'John'
);
// string(29) "Call Foo::doSomething('John')"
```

More details about the `call()` method are provided in the section [“Container method for callable types”](call/index.md).

### set()

The container implements the `\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface` interface, which provides the `set()` method for directly adding an object (an instantiated class) to an already assembled dependency container.

```php
<?php
declare(strict_types=1);

use App\Services\Bar;

$bar = new Bar(
    // set some dependencies.
);

// the identifier will be specified as 'App\Services\Bar'
$container->set($bar::class, $bar);
```

More details about the `set()` method are provided in the section [“Dynamically Adding Definitions to a Container”](container-builder/set.md).

### Implementation Features of Certain Classes and Interfaces { #resolve-class-interface }

When injecting dependencies of the types listed below, the current container will always be obtained:
- `\Psr\Container\ContainerInterface`
- `\Kaspi\DiContainer\Interfaces\DiContainerInterface`
- `\Kaspi\DiContainer\DiContainer`

Example:
```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Psr\Container\ContainerInterface;

class Foo
{
    public function __construct(
        public readonly ContainerInterface $container//[!code warning]
    ) {}
}

$container = (new DiContainerBuilder())->build();

var_dump($container === $container->get(Foo::class)->container);
// (bool) true
```

### Dependency injection declaration { #using }

Dependency injection can be defined in [configuration files](php-definition/index.md) or via [attributes](attribute-definition/index.md); it is also possible to combine both methods of definition.


To build the container, use the [special `DiContainerBuilder` class](container-builder/index.md).

#### Contents of the documentation { #toc }

* 👷‍♂️ [Building the dependency container](container-builder/index.md).
* ⚙️ [Configuration for DiContainer](container-config/index.md).
* 🐘 [Declaration in configuration files](php-definition/index.md).
* #️⃣ [Declaration via attributes](attribute-definition/index.md).
* 📦 [The `call()` container method for callable types](call/index.md).
* 🔖 [Tags – labels for container definitions](tags/index.md).
* 📋 [Container parameters](container-parameters/index.md).
* 🗳️ [Declaring an object in a runtime container](runtime-definition/index.md).
* 🏭 [Using factory methods for dependency injection](factories/index.md).
* 🧹 [Dependency container reset](container-reset/index.md).
* ♻️ [Resetting object state for long-running processes](object-resetters/index.md).
* 💤 [Injection of lazy objects by the container](lazy-injection/index.md).

