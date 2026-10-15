# «Метод-приоритета» по умолчанию { #priority-by-method-default }

## Обзор { #overview }

Для случаев когда определить приоритет элемента коллекции из тега не удается, используется «**метод-приоритета**» по умолчанию.
При отсутствии в PHP классе «**метода-приоритета**» по умолчанию, элемент коллекции получит приоритет со значением `null`.

В [файле конфигураций определений](../../container-builder/configuration_files.md) имя метода указывается в параметре `$priorityDefaultMethod` [хелпер функции `diTaggedAs()`](../../php-definition/di-tagged-as.md).

::: code-group

```php [файл конфигураций tagged_service.php]   
// file: /app/config/tagged_service.php
use Generator;
use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
   yield diAutowire(Foo::class)
      ->bindArguments(
         servies: diTaggedAs(
            'tags.ordered',// [!code ++]
            priorityDefaultMethod: 'getPriorityForCollection'// [!code ++]
         )
      );
      

    yield diAutowire(Bar::class)
        ->bindTag(name: 'tags.ordered');
};
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
   // …

   // 🚩 без явного указания приоритета и «метода-приоритета» в теге,
   // приоритет может быть получен через «метода-приоритета» по умолчанию
   public static function getPriorityForCollection(string $tag): string|int|null// [!code ++]
   {// [!code ++]
      return match ($tag) {// [!code ++]
         'tags.internal' => 100,// [!code ++]
         'tags.validator' => 'GROUP100:0001',// [!code ++]
         default => null,// [!code ++]
      };// [!code ++]
   }// [!code ++]
}
```

:::


Через PHP атрибуты «метод-приоритета» по умолчанию указывается в параметре атрибута `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod`.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
   public function __construct(
      #[TaggedAs(// [!code ++]
         name: 'tags.ordered',// [!code ++]
         priorityDefaultMethod: 'getPriorityForCollection',// [!code ++]
      )]// [!code ++]
      public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered')]// [!code ++]
final class Bar
{
    // …
       // 🚩 без явного указания приоритета и «метода-приоритета» в теге,
   // приоритет может быть получен через «метода-приоритета» по умолчанию
   public static function getPriorityForCollection(string $tag): string|int|null// [!code ++]
   {// [!code ++]
      return match ($tag) {// [!code ++]
         'tags.internal' => 100,// [!code ++]
         'tags.validator' => 'GROUP100:0001',// [!code ++]
         default => null,// [!code ++]
      };// [!code ++]
   }// [!code ++]
}
```

:::

<!--@include: ../../php-definition/_include/bind-tag_notes.md-->
