Параметр `$options` может содержать дополнительные метаданные для устанавливаемого тега представленные массивом.

Ключ массива `$options` это непустая строка, а значение это простой php тип (_`string`, `int`, `bool`, `null` или `array` из этих типов_).

<span id="reserved-keys"/>

> [!WARNING]
> В параметре `$options` зарезервирован ключ массива `priority.method` – значение типа `string`, для **метода-приоритета**.
> ```php
> $options = [
>   // …
>   'priority.method' => 'staticMethodName' // [!code ++]
> ]
> ```
> Подробнее о [приоритете в коллекции](../collection-priority.md#priority-by-method).
