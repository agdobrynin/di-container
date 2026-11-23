---
aside: false
title: Naming convention for tags and metadata
---
### Naming convention for tags and metadata { #title }

It is recommended to follow a naming convention for tag names and tag metadata names:
- use lowercase letters for tag names and metadata keys.
- use an underscore `_` to separate parts of a name if it consists of multiple words.
- use a dot `.` to separate groups within a definition.

#### Example of using names according to a convention { #example-naming-convention }

Declaration in the definitions configuration file:

```php
use function Kaspi\DiContainer\diAutowire;

diAutowire(SomeClass::class)
    ->bindTag(
        'tags.any_service',
         options: [
            'some_info.data' => 'foo',
            'some_info.current_value' => 'baz',
        ]
     );
```

Declaration via PHP attributes:

```php
use Kaspi\DiContainer\Attributes\Tag;

#[Tag(
    'tags.any_service',
    options: [
        'some_info.data' => 'foo',
        'some_info.current_value' => 'baz',
    ]
)]
final class Foo {}
```
