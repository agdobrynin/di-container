<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

use function sprintf;
use function trim;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class ProxyClosure implements DiAttributeInterface
{
    /**
     * @param class-string|non-empty-string $id class name or container identifier
     */
    public function __construct(private string $id)
    {
        if ('' === trim($id)) {
            throw new AutowireAttributeException(
                sprintf('The attribute #[%s] must have $id parameter as non-empty string.', self::class)
            );
        }
    }

    /**
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->id;
    }
}
