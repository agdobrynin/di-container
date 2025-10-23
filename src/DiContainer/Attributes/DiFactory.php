<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

use function is_a;
use function sprintf;

#[Attribute(Attribute::TARGET_CLASS)]
final class DiFactory implements DiAttributeServiceInterface
{
    /**
     * @param class-string $id
     */
    public function __construct(private string $id, private ?bool $isSingleton = null)
    {
        if (!is_a($id, DiFactoryInterface::class, true)) {
            throw new AutowireAttributeException(
                sprintf('The attribute #[%s] must have an $id parameter as class-string. Class must have implement "%s" interface. Got: "%s".', self::class, DiFactoryInterface::class, $id)
            );
        }
    }

    /**
     * @return class-string
     */
    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }
}
