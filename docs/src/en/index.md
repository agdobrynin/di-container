---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
isHome: true

hero:
    name: kaspi/di-container
    text: Dependency injection container for PHP 8.1 – 8.5
    tagline: A lightweight codebase with no external dependencies or unnecessary «clutter».
    image:
        src: /logo_optimize.svg
        alt: kaspi/di-container
    actions:
      - theme: brand
        text: Manual
        link: documentation/
      - theme: alt
        text: Cookbook
        link: cookbook/
      - theme: alt
        text: GitHub
        link: https://github.com/agdobrynin/di-container
features:
    - title: Autowire
      details: Automatic dependency injection for parameters of class constructor and callable type.
      icon: 🛠️
    - title: Lazy injection
      details: Lazy initialization of injected objects.
      icon: 💤
    - title: Attribute-driven
      details: The container can be configured using native PHP 8+ attributes.
      icon:  #️⃣ 
    - title: Zero configuration
      details: If a class has no dependencies or depends only on other concrete classes, the container does not need to be told how to instantiate that class.
      icon: 🧊
    - title: Definition Tags
      details: Retrieving a collection of definitions and services from a container by tag.
      icon: 🏷️
    - title: Compiling the Container
      details: Generating a configured container into PHP code, optimized specifically for your configuration and your classes.
      icon: 📦

---

## DiContainer overview { #overview }

Kaspi/di-container is a dependency injection container for PHP >= 8.1 that implements the [PSR-11](https://www.php-fig.org/psr/psr-11/).

## Install { #install }

Use the PHP dependency manager [composer](https://getcomposer.org/) to install the "kaspi/di-container" package.

```shell
composer require kaspi/di-container
```

## Quck start { #quick-start }

PHP-classes:

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

<VPButton href="documentation/">Start exploring the manual</VPButton>
