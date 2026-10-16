# #️⃣  Настройка тегов через php атрибут { #title }

## Обзор { #overview }

[Атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) устанавливает тег и метаданные для PHP класса.

[Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) внедряет коллекцию элементов из контейнера отмеченных тегом.

<!--@include: ../_include/need_import_php_class.md-->

<!--@include: ../attribute-definition/_include/tagged_as_example.md-->

## Внедрение коллекции для параметров переменной длины { #inject-by-attribute-tagged-as-variadic }

Параметр переменной длины так же поддерживает внедрение коллекций элементов из контейнера отмеченных тегом.

```php
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Qux
{
    public function __construct(
        #[TaggedAs('tags.group_one')]
        #[TaggedAs('tags.group_two')]
        iterable ...$collection
    ) {}

}
```

> [!TIP]
> <!--@include: ../_include/variadic_params.md-->
