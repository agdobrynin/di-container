# Работа с тегами в контейнере { #title }

## Обзор { #overview }

Теги позволяют расширить возможности внедрения коллекций в параметры методов и функций. В коллекцию собираются элементы из контейнера отмеченные тегом с одинаковым именем.

Внедрение зависимости применимо к параметрам с типом:
- `iterable`
    - `\Traversable`
        - `\Iterator`
- `\ArrayAccess`
- `\Psr\Container\ContainerInterface`
- `array` (_требуется передать аргумент `false` для параметра `$isLazy`_)
- `\ArrayAccess & \Iterator & \Psr\Container\ContainerInterface`

Любое определение в контейнере может быть отмечено одним или несколькими тегами. Каждый тег может содержать [метаданные](tag-metadata.md).

Объявить теги определения контейнера можно используя:
- Хелпер функции:  [`diAutowire()`](../php-definition/di-autowire.md#bindtag), [`diCallable()`](../php-definition/di-callable.md#bindtag), [`diProxyClosure()`](../php-definition/di-proxy-closure.md#bindtag), [`diValue()`](../php-definition/di-value.md#bindtag).
- Атрибуты: [`Autowire`](../attribute-definition/autowire.md#tags), [`Tag`](../attribute-definition/tag.md).

Объявить внедрение зависимости коллекции элементов в параметр можно через:
- Хелпер функции: [`diTaggedAs()`](../php-definition/di-tagged-as.md).
- Атрибуты: [`TaggedAs`](../attribute-definition/tagged-as.md).

<!--@include: ../_include/need_import_php_class.md-->

## Ленивая коллекция { #collection-is-lazy }

Элемент «ленивой коллекции» инициализируется только в момент непосредственного обращения к конкретному элементу, это позволяет отложить создание зависимостей объекта до момента их фактического использования, а не раньше.

«Ленивая коллекция» определяется параметром `$isLazy` в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) или в [атрибуте `TaggedAs`](../attribute-definition/tagged-as.md).

> [!WARNING]
> «Ленивую коллекцию» нельзя внедрить в параметр с типом `array`. Для параметров с типом `array` необходимо установить значение `false` для параметра `$isLazy`.

## Идентификатор элемента в коллекции { #collection-key }

Внедряемая коллекция по умолчанию в качестве идентификаторов элементов получает идентификатор контейнера [^ContainerId] этого элемента.

Это поведение можно изменить через параметры `$useKeys`, `$key`, `$keyDefaultMethod` [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибута `TaggedAs`](../attribute-definition/tagged-as.md).
Идентификаторы элементов в коллекции могут быть представлены произвольной строкой или целыми числами.

Больше информации в разделе документации [«Идентификаторы элементов коллекции»](using-collecton-key/index.md).

## Содержание раздела { #toc }

- 🐘 [Объявление тега в файлах конфигураций](php-definition-tag.md).
- #️⃣  [Объявление тега через атрибут](attribute-definition-tag.md).
- 📑 [PHP интерфейс как имя тега](interface-as-tag.md).
- 🚫 [Исключение элементов из коллекции](exclide-from-collection.md).
- ⬆️ [Приоритет элементов коллекции](collection-priority/index.md).
- 🔑 [Идентификаторы элементов коллекции](using-collecton-key/index.md).

<!--@include: ../_include/term_notes.md-->
