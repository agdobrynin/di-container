# 🐘 Настройка тегов в файлах конфигураций { #title }

## Обзор { #overview }

Для указания тегов и метаданных используйте метод `bindTag()` [^BindTag], описанный интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`.

[Хелпер функция `diTaggedAs()`](../php-definition/di-tagged-as.md) внедряет коллекцию элементов из контейнера отмеченных тегом.

<!--@include: ../_include/need_import_php_class.md-->

## Пример использования хелпер функции { #example-inject-by-helper-fn-di-tagged }

<!--@include: ../php-definition/_include/di-tagged-as-example.md-->

## Внедрение коллекции для параметров переменной длины { #inject-by-di-tagged-as-variadic }

Параметр переменной длины так же поддерживает внедрение коллекций элементов из контейнера отмеченных тегом.

::: code-group

```php [tagged_services.php]
// file: /app/config/tagged_services.php
use Generator;
use App\Services\Bar;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindArguments(
            srvGroup_1: diTaggedAs('tags.group_1'),
            srvGroup_2: diTaggedAs('tags.group_2'),
        );
};
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

class Bar
{
    public function __construct(iterable ...$srvGroup) {}

}
```

:::

> [!TIP]
> <!--@include: ../_include/variadic_params.md-->

<!--@include: ../php-definition/_include/bind-tag_notes.md-->
