---
outline: [2,4]
title: Helper function diValue()
---
### Helper function diValue()

The `diValue()` helper function injects a dependency "as is."

Function signature:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
 
\Kaspi\DiContainer\diValue(
    mixed $value
): DiDefinitionTagArgumentInterface
```

Parameters:
- `$value` – the value passed into the container.

The `diValue()` function returns an object that provides the following methods:
- `bindTag()` – adds a tag containing metadata for identification.

### bindTag()

Tags allow a declared definition to be associated with a collection of elements marked with the same tag.

<!--@include: ./_include/bind_tag.md-->

### Container identifier { #container-id }

To make a declared definition accessible via the container's `get()` and `has()` methods, you must specify a container identifier[^ContainerId] in the configuration file that corresponds to the declared definition.

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Bar;

$container = (new DiContainerBuilder())
    ->load('/app/config/emails.php')//[!code ++]
    ->build();

$emails = $container->get('tasks.emails');//[!code highlight]

var_dump($email);
```

```php [emails.php]
<?php
declare(strict_types=1);

// file: /app/config/emails.php // [!code highlight]

use function Kaspi\DiContainer\diValue;

return static function(): Generator {
     yield 'tasks.emails' => diValue([// [!code ++]
        'runner@company.inc',// [!code ++]
        'monitor@company.inc',// [!code ++]
    ]);// [!code ++]
};
```

::: 

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
array(2) {
  [0]=>
  string(18) "runner@company.inc"
  [1]=>
  string(19) "monitor@company.inc"
}
```

<!--@include: ../_include/term_notes.md-->
