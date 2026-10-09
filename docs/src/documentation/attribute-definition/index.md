# #️⃣ DiContainer c конфигурированием через PHP атрибуты { #title }

## Обзор { #overview }

[В конфигурации контейнера](../container-config/index.md#use-attribute) по умолчанию параметр `$useAttribute` включён.

Для указания контейнеру каким образом нужно внедрять зависимости в PHP классах или вызываемых типах используется механизм конфигурирования через PHP атрибуты.

Для создания настроенного контейнера используется класс-строитель [DiContainerBuilder](../container-builder/index.md).

PHP атрибуты содержат мета-данные для конфигурирования определений контейнера – передать аргументы для разных типов, указать дополнительное конфигурирование через сеттер методы или добавить теги.

При конфигурировании контейнера можно совмещать PHP атрибуты и PHP определения.

> [!WARNING]
> PHP атрибуты имеют более высокий приоритет при конфигурировании чем [конфигурация в стиле PHP определений](../php-definition/index.md).
>

> [!IMPORTANT]
> Для каждого идентификатора контейнера и его определения необходимо выбрать только один способ конфигурации – через php атрибуты или через [файлы-конфигураций](../container-builder/configuration_files.md). 

### Доступные атрибуты { #attributes }
- [Autowire](autowire.md) – конфигурирование PHP класса или их набора в контейнере.
- [AutowireExclude](autowire-exclude.md) – исключить внедрение PHP класса или интерфейса.
- [Setup](setup.md) – внедрение зависимости в PHP класс через мутабельный сеттер-метод.
- [SetupImmutable](setup-immutable.md) – внедрение зависимости в PHP класс через иммутабельный сеттер-метод.
- [SetupPriority](setup-priority.md) – приоритет сеттер-метода.
- [Inject](inject.md) – внедрение зависимости по идентификатору контейнера.
- [InjectByCallable](inject-by-callable.md) – внедрение зависимости через тип `callable`.
- [Service](service.md) – указывает реализацию PHP интерфейса.
- [DiFactory](di-factory.md) – внедрение зависимости через паттерн «фабрика».
- [ProxyClosure](proxy-closure.md) – внедрение «ленивой» зависимости.
- [Tag](tag.md) – тег для PHP класса.
- [TaggedAs](tagged-as.md) – внедрение коллекции по тегу.
- [Parameter](parameter.md) – внедрение зависимости из «параметров контейнера».
- [ParameterRuntime](#parameterruntime) – внедрение зависимости из «параметров контейнера времени исполнения».

-----


## ParameterRuntime
Параметр контейнера времени исполнения. [Аналогичен PHP атрибуту `Parameter`](#parameter), но значение необходимо установить в контейнер
после его формирования.

Сигнатура php атрибута:
```php
#[ParameterRuntime(string $name = '', ?string $message = null)]
```
Параметры:
- `$name` – имя параметра контейнера.
- `$message` – дополнительное сообщение, если параметр контейнера еще не определен.

> [!NOTE]
> Атрибут может быть применен несколько раз к параметрам переменной длины (_variadic parameter_).

```php
namespace App\Services;

use App\Services\Qux;
use Kaspi\DiContainer\Attributes\ParameterRuntime;

final class Bar {
    public function __construct(
        private Qux $qux,
        #[ParameterRuntime('foo.parameter')]
        private string $value,
    ) {}
}
```

> [!NOTE]
> Подробное [описание работы с параметрами контейнера](09-container-parameters.md#параметры-контейнера-определяемые-во-время-выполнения).

