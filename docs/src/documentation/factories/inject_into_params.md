## Внедрение в параметры метода

С помощью фабрики также доступно разрешение зависимостей для параметров метода (функции).

```php
namespace App\Factories;

use use App\Interfaces\ApiClientInterface;

final class ApiClientFactory
{
    public static function createApiV2(): ApiClientInterface
    {
        //..
        return $object;
    }
}
```
В хелпер функции:
```php
// src/config/services.php

use App\Classes\Baz;
use App\Factories\ApiClientFactory;
use function Kaspi\DiContainer\{diAutowire, diFactory};

return static function (): \Generator {

    yield diAutowire(Baz::class)
        ->bindArguments(
            apiClient: diFactory(
                [ApiClientFactory::class, 'createApiV2']
            )
        );

};
```

В php атрибуте:
```php
namespace App\Classes;

use App\Interfaces\ApiClientInterface;
use App\Factories\ApiClientFactory;
use Kaspi\DiContainer\Attributes\DiFactory;


class Baz {

    public function __construct(
        #[DiFactory([ApiClientFactory::class, 'createApiV2'])]
        private readonly ApiClientInterface $apiClient
    ) {}

}
```