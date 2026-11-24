> [!IMPORTANT]
> The identifier method must be declared within a PHP class using the `public` and `static` modifiers. The method must return a `string`.
>
> The method accepts two arguments, which are passed by the container:
>  - `string $tag` – the tag name.
>  - `array $options` – [tag metadata](/en/documentation/tags/tag-metadata.md).
>
> These parameters can be used to define the logic for generating an identifier for a collection element.