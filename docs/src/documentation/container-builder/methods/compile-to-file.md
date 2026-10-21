---
outline: [2, 4]
---
# Компиляция контейнера { #title }

## Обзор { #overview }

Для повышения производительности контейнера зависимостей реализован компилятор который преобразует конфигурацию контейнера в готовый к использованию PHP-код сохраняемый в файл, чтобы при следующих запусках контейнер загружался мгновенно, минуя этап парсинга конфигурации.

## compileToFile()

Метод `compileToFile()` объявляет параметры компиляции контейнера в PHP-код.

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder::compileToFile(
    string $outputDirectory,
    string $containerClass,
    int $permissionCompiledContainerFile = 0666,
    bool $isExclusiveLockFile = true,
    array $options = []
): static;
```

Параметры:
- `$outputDirectory` – директория назначения.
- `$containerClass` – FQCN[^FQCN] скомпилированного контейнера.
- `$permissionCompiledContainerFile` – права доступа к файлу.
- `$isExclusiveLockFile` – эксклюзивная блокировка файла во время записи PHP-кода в конечный файл.
- `$options` – дополнительные [настройки компилятора](#compile-options).

> [!IMPORTANT]
> Для обеспечения максимальной производительности файл содержащий PHP код контейнера компилируется один раз, при наличии файла компиляция не требуется и готовый PHP код контейнера сазу доступен в приложении.
>
> При развёртывании новых версий контейнера (измененных) в продуктивной среде вы должны удалить сгенерированный файл (или каталог, который его содержит), чтобы обеспечить повторную компиляцию контейнера.

> [!NOTE]
> Имя файла для скомпилированного контейнера генерируется на основании параметров `$outputDirectory` и `$containerClass`.
> Сформированное полное имя файла это директория назначения `$outputDirectory` плюс короткое имя класса из `$containerClass` без учёта указанного пространства имён.

Пример настройки компиляции контейнера:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$builder = new DiContainerBuilder();

// ...

$builder->compileToFile(//[!code ++]
    outputDirectory: '/app/var/container',//[!code ++]
    containerClass: 'App\Core\FooContainer',//[!code ++]
);//[!code ++]

$container = $builder->build();
```

> [!TIP]
> Будет сформирован PHP класс `\App\Core\FooContainer` и готовый к использованию PHP-код контейнера будет сохранен в файл `/app/var/container/FooContainer.php`.
> 

> [!WARNING]
> Директория указанная в параметре `$outputDirectory` должна существовать и быть доступна для чтения и записи.
>

## Дополнительные настройки компилятора { #compile-options }

Компилятор может получать ряд дополнительных настроек в параметре `$options` метода `compileToFile()` в виде ассоциативного массива с заранее определенным ключами.

### Принудительная повторная компиляция { #force_rebuild }

Параметр `'force_rebuild'` принимает тип `bool`, значение по умолчанию `false`.

Для обеспечения максимальной производительности файл содержащий PHP код контейнера компилируется один раз, при наличии файла компиляция не требуется и готовый PHP код контейнера сазу доступен в приложении. 

Указывая значение `'force_rebuild' => true` компиляция контейнера будет происходить каждый раз при сборке контейнера.

### Обработка исключений во время компиляции { #invalid_behavior }

Параметр `'invalid_behavior'` принимает тип `\Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum`, значение по умолчанию `InvalidBehaviorCompileEnum::ExceptionOnCompile`.

При компиляции контейнера может быть выброшено исключение в связи с несуществующим PHP классом или отсутствующей конфигураций определения контейнера. Параметр `'invalid_behavior'` и его значение указывает компилятору какое поведение будет при выбросе исключений во время компиляции.

1. Остановить компиляцию и выбросить исключение с сообщением:
> `'invalid_behavior' => InvalidBehaviorCompileEnum::ExceptionOnCompile`

2. Не останавливать компиляцию контейнера, полученное исключение перевести в PHP код скомпилированного контейнера:
> `'invalid_behavior' => InvalidBehaviorCompileEnum::RuntimeContainerException`

### Реализация трансформера определений контейнера { #di_definition_transformer }

Параметр `'di_definition_transformer'` принимает объект, значение по умолчанию пустая строка.

PHP класс отвечающий за трансформацию определений контейнера в PHP классы необходимые компилятору. 

Разработчик может создать собственный PHP класс, который будет реализовывать интерфейс `\Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface`, и объект этого класса передать в параметр `'di_definition_transformer' => $customTransformer`.

### Хранение скомпилированных определений контейнера { #compiled_entries }

Параметр `'compiled_entries'` принимает объект, значение по умолчанию пустая строка.

PHP класс отвечающий за хранение скомпилированных определений контейнера.

Разработчик может создать собственный PHP класс, который будет реализовывать интерфейс `\Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface`, и объект этого класса передать в параметр `'compiled_entries' => $customCompiledEntries`.

<!--@include: ../../_include/term_notes.md-->
