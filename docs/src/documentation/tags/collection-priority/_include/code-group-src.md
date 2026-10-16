
::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(public readonly iterable $services) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

final class Qux
{
    // …
}
```

:::
