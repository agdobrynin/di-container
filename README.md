# DiContainer

[PSR-11](https://www.php-fig.org/psr/psr-11/) compatible dependency injection container for PHP with automatic dependency bundling.

## Features

- **Autowiring**: automatic dependency injection — you declare dependencies using type hints, and the container handles the rest. In most cases, you do not need to define object configuration;
- **Lazy injection**: allows for the deferred creation of object dependencies until the moment they are actually used;
- Attribute-driven: declarative dependency injection using native PHP 8+ attributes;
- **Zero configuration**: no object configuration is required if a PHP class has no dependencies or depends only on other concrete classes;
- **Definition tags**: allows for the injection or retrieval of a collection of items from the container that share the same tag name;
- **Compiling the container**: generates a configured container as high-performance PHP code, optimized specifically for your configuration and classes;
- **PSR-11**: compliant with the PSR-11 standard for dependency injection containers;
- **Object state reset**: a mechanism for resetting the state of objects within long-running processes;

## The full documentation
- [English version](https://agdobrynin.github.io/di-container/en/)
- [Russian version](https://agdobrynin.github.io/di-container/)


## Requirements

- PHP 8.1 - 8.5
- ext-tokenizer
- [psr/container](https://packagist.org/packages/psr/container) ^2.0

## Installation

```shell
composer require kaspi/di-container
```

## Quick start

Let’s look at a simplified example involving a set of PHP classes for which we need to implement automatic dependency injection using the "kaspi/di-container" dependency injection container.

```php
<?php
declare(strict_types=1);

// file: /app/src/Controllers/PostController.php

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
```php
<?php
declare(strict_types=1);

// file: /app/src/Services/Mail.php

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
```php
<?php
declare(strict_types=1);

// file: /app/src/Services/Envelope.php

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
```php
<?php
declare(strict_types=1);

// file: /app/src/Models/Post.php

namespace App\Models;

// Data model — blog post
class Post
{
    public string $title;
    // ...
}
```

**In action:**

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use App\Controllers\PostController;
use App\Models\Post;

$container = (new DiContainerBuilder())
    ->build();

if ($container->has(PostController::class)) {
    // An object with dependencies injected via the constructor
    $postController = $container->get(PostController::class);
    
    // let's fill in the post details
    $post = new Post();
    $post->title = 'Lorem ipsum';

    // calling a method on the obtained object
    $postController->send($post);
}
```

The dependency injection container will perform the following actions for the PHP class `\App\Controllers\PostController`:

```php
$postController = new App\Controllers\PostController(
    new App\Services\Mail(
        new App\Services\Envelope()
    )
);
```

# Resources

## Contributing

We welcome contributions from the community toward improving the project.

### Bug Reports

Create a [new bug report](https://github.com/agdobrynin/di-container/issues/new?title=Bug&labels=bug&body=1.%20Operation%20system%3A%0A2.%20PHP%20version%3A%20%0A3.%20kaspi%2Fdi-container%20version%3A%20%0A%0A---%0A%0A**Steps%20to%20reproduce%20the%20error%3A**%20%0A%0A) with description:
1. PHP version
2. Kaspi/di-container version
3. Operation system: platform (UNIX, WINDOWS), os version
4. Steps to reproduce the error

### Feature Requests

Create a [new issue](https://github.com/agdobrynin/di-container/issues/new?title=New%20feature&labels=enhancement%2Cfeature&body=**Description%20of%20the%20new%20feature.**%0A--%0Adescribe%20the%20feature...%0A%0A**A%20possible%20solution%2C%20ideally%20with%20usage%20examples.**%0A--%0A%60%60%60php%0A%3C%3Fphp%0A%0Aecho%20%27Hello%20world%27%3B%0A%60%60%60%0A) with description:

- Describe what problem the new feature will solve
- A possible solution, ideally with usage examples
- Expect feedback from the maintainers

###  Pull Requests

- Fork the repository
- Create a branch: `git checkout -b feature/wonderful-feature`
- Create tests for the new code
- Create a [pull request](https://github.com/agdobrynin/di-container/pulls)

## Development

Requirements:
- PHP 8.1 - 8.5
- ext-tokenizer

Clone repository:
```shell
git clone https://github.com/agdobrynin/di-container.git &&
cd di-container
```
Install dependencies:
```shell
composer install
```

### Tests

Run tests without calculating code coverage:
```shell
composer test
```

Running tests with code coverage analysis:
```shell
composer test-cover
```

### Static code analysis

For static analysis, we use the [PHPStan](https://github.com/phpstan/phpstan) package:
```shell
composer stat
```

### Code style
To bring the code into compliance with standards, we use **php-cs-fixer**, which is declared in the composer dev dependency:

```shell
composer fixer
``` 

### Using a Docker image with PHP 8.1, 8.2, 8.3, 8.4, 8.5

You can specify the image and PHP version in the `.env` file using the `PHP_IMAGE` key.
By default, the container is built using the `php:8.1-cli-alpine` image.

Build docker image:
```shell
docker-compose build
```

Install PHP composer dependencies:
```shell
docker-compose run --rm php composer install
```
🔔 If `make` is installed on the system:
```shell
make install
```
#### Tests in Docker

Running tests without a code coverage report:
```shell
docker-compose run --rm php vendor/bin/phpunit --no-coverage
```
🔔 If `make` is installed on the system:
```shell
make test
```

Tests with a code coverage report:
```shell
docker-compose run --rm php vendor/bin/phpunit
```
🔔 If `make` is installed on the system:
```shell
make test-cover
```

#### PHPStan static code analysis

```shell
docker-compose run --rm php vendor/bin/phpstan
```
🔔 If `make` is installed on the system:
```shell
make stat
```

#### Run all stages of code verification

- Fix code-style
- Run stat analyzer
- Run tests

```shell
make all
```

### Running tests for all supported PHP versions via Docker images

Before running the tests, the `vendor` directory and the `composer.lock` file are deleted, dependencies are installed, and only then are the tests executed:
```shell
make test-supports-php
```

### Switch to interactive mode

You can work within a shell in a Docker container:
```shell
docker-compose run --rm php sh
```
