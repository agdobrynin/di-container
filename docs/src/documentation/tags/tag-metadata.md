---
aside: false
---
# Метаданные тега { #title }

Метаданные тега указывается парой ключ-значение:
- **имя метаданных** – произвольная непустая строка.
- **значение** – [скалярный тип](https://www.php.net/manual/en/language.types.type-system.php#language.types.type-system.atomic.scalar), `null` или `array` состоящий из этих типов.

Метаданные для тега передаются через параметр `$options` в методе `bindTag()` хелпер функций [`diAutowire()`](../php-definition/di-autowire.md#bindtag),
[`diCallable()`](../php-definition/di-callable.md#bindtag), [`diProxyClosure()`](../php-definition/di-proxy-closure.md#bindtag), [`diValue()`](../php-definition/di-value.md#bindtag)
или в [атрибуте `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).

<span id="reserved-keys"/>

> [!WARNING]
> В метаданных зарезервировано имя `priority.method` для **метода-приоритета**.
> ```php
> $options = [
>   // …
>   'priority.method' => 'staticMethodName' // [!code ++]
> ]
> ```
> Подробнее о [приоритете в коллекции](collection-priority.md#priority-by-method).
