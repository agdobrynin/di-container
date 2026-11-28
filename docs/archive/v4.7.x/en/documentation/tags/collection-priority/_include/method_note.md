> [!IMPORTANT]
> The method must be declared within a PHP class using the `public` and `static` modifiers. It must return an `int`, a `string`, or `null`.
>
> The method accepts two arguments, which are passed by the container:
>  - `string $tag` – the name of the tag.
>  - `array $options` – [tag metadata](/en/documentation/tags/tag-metadata.md).
>
> These parameters can be used to implement logic for determining the tag's priority.
