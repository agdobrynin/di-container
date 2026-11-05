TMP!

При объявлении внедрения зависимости через фабрику выбранное значение для класса не влияет на получаемый результат выполнения фабричного метода. [Фактическое имя класса фабрики](factory_class_container_id.md) зависит только от объекта получаемого через метод контейнера `get()` и значение имени класса. Однако разумное значение для имени класса фабрики может упростить его использование при конфигурировании.

Показать на примере как устроено имя класса фабрики:

```php
\Kaspi\DiContainer\diFactory(['foo_id', 'doSomething'])
```

# Фактическое имя класса фабрики { #title }

## Обзор { #overview }

Фактическое имя класса фабрики зависит только от объекта (определения), получаемого от контейнера.
В некоторых сценариях использования можно указывать идентификатор контейнера в качестве
имени класса фабрики при условии, что определение (_definition_) по указанному идентификатору,
реализует указанный фабричный метод.

```php
namespace App\Factories;

use App\Classes\Foo;
use Psr\Log\LoggerInterface;

class ClassFactory
{
    private LoggerInterface $logger;

    public function __construct(private Bar $bar) {}
    
    public function withLogger(LoggerInterface $logger): self
    {
        $new = clone $this;
        $new->logger = $logger;
        
        return $new;
    }

    public function create(): Foo
    {
        $foo = new Foo($this->bar);
        // дополнительное конфигурирование `$foo`
        if (isset($this->logger)) {
            $this->logger->info('Configured class via factory', $foo);
        }
        
        return $foo;
    }
}
```

```php
// config/services.php
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\{diAutowire, diGet};

return function () {
    
    // доступен через метод контейнера `get('factories.class_factory.config_one')`
    yield 'factories.class_factory.config_one' => diAutowire(ClassFactory::class)
        ->setupImmutable('withLogger', diGet('services.app_logger'));

    // использование идентификатора контейнера
    yield Foo::class => diFactory([
        'factories.class_factory.config_one', 'create'
    ]);

};
```
Объявление фабрики через php атрибут:
```php
use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(['factories.class_factory.config_one', 'create'])]
class Foo{}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$container->get(\App\Classes\Foo::class);
```
> [!NOTE]
> Класс `\App\Classes\Foo` будет создан через вызов
> метода контейнера `$object = $container->get('factories.class_factory.config_one')`
> и исполнением метода `$object::create()`.
>
> При разрешении фабрики, контейнер попытается разрешить зависимость
> `ClassFactory::$bar` в конструкторе на основе конфигурации.
>