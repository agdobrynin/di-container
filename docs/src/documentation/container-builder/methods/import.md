# Объявление PHP классов в контейнере из файловой системы { #title }

## Обзор { #overview }

Объявить PHP классы и интерфейсы в контейнере можно в автоматическом режиме через сканирование файловой системы. При анализе PHP классов и интерфейсов из файловой системы учитываются примененные атрибуты если в конфигурации контейнера указано [использование PHP атрибутов](../../container-config/index.md#use-attribute).

## import()

Метод `import()` объявляет PHP классы из указанных директорий файловой системы.

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder::import(
    string $namespace,
    string $src,
    array $excludeFiles = [],
    array $availableExtensions = ['php'],
): static;
```

Параметры:

- `$namespace` – префикс пространства имён PHP классов.
- `$src` – директория источник.
- `$excludeFiles` – исключить из объявления PHP классы в находящиеся в файлах.
- `$availableExtensions` – расширения файлов PHP классов.

> [!NOTE]
> Параметр `$excludeFiles` использует синтаксис шаблонов из [php функции `\fnmatch()`](https://www.php.net/manual/en/function.fnmatch.php).

Объявить PHP классы можно из нескольких директорий вызывая последовательно метод `import()` с указанием уникального значения в параметре `$namespace`:

```php
use Kaspi\DiContainer\DiContainerBuilder;
 
$builder = (new DiContainerBuilder())
  ->import(namespace: 'App\Services\\', src: '/app/src/Services')
  ->import(namespace: 'App\Actions\\', src: '/app/src/Actions')
;
```

PHP классы найденные в исключенных файлах, параметр `$excludeFiles`, будут недоступны контейнеру через метод `get()`, а следовательно нельзя будет внедрить такой PHP класс.


## Пример объявления PHP классов из файловой системы { #example }

Необходимо объявить PHP классы из директории проекта `'/app/src'`. PHP классы в директории `''/app/src/Various/'` необходимо исключить из контейнера.  

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(
        namespace: 'App\\',
        src: '/app/src/',
        excludeFiles: [
            '/app/src/Various/*.php',
        ]
    )
    ->loadParameters('/app/config/report_parameters.php')
    ->build();
```

<!--@include: ../../_include/term_notes.md-->
