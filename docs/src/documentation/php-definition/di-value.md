# diValue()

## Обзор

Хелпер функция `diValue()` внедряет зависимость «как есть».

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
 
\Kaspi\DiContainer\diValue(
    mixed $value
): DiDefinitionTagArgumentInterface
```

Параметры:
- `$value` – переданное значение в контейнер.

Функция `diValue()` возвращает объект предоставляющий методы:
- `bindTag()` – добавляет тег с метаданными для определения.

## bindTag()

Теги позволяют отнести объявляемое определение к коллекции элементов отмеченных одним тегом.

<!--@include: ./_include/bind_tag.md-->

## Идентификатор контейнера { #container-id }

Чтобы обеспечить доступность объявляемого определения через метод контейнера `get()` и `has()` необходимо указать в файле конфигураций идентификатор контейнера[^ContainerId] соответствующий объявленному определению.

::: code-group

```php [emails.php]
<?php
declare(strict_types=1);

// file: /app/config/emails.php // [!code highlight]

use function Kaspi\DiContainer\diValue;

return static function(): Generator {
     yield 'admin.email.tasks' => diValue('runner@company.inc'); // [!code ++]
};
```

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Bar;

$container = (new DiContainerBuilder())
    ->load('/app/config/emails.php')//[!code highlight]
    ->build();

$email = $container->get('admin.email.tasks');//[!code highlight]

var_dump($email);
// string(18) "runner@company.inc"
```

::: 

<!--@include: ../_include/term_notes.md-->
