<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use InvalidArgumentException;
use Iterator;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Interfaces\FinderFullyQualifiedNameCollectionInterface;

use function sprintf;

final class FinderFullyQualifiedNameCollection implements FinderFullyQualifiedNameCollectionInterface
{
    /**
     * @var array<non-empty-string, FinderFullyQualifiedNameInterface>
     */
    private array $import = [];

    public function add(FinderFullyQualifiedNameInterface $finderFullyQualifiedName): static
    {
        $existItem = $this->import[$finderFullyQualifiedName->getNamespace()] ?? null;

        if (null !== $existItem) {
            throw new InvalidArgumentException(
                sprintf('The namespace "%s" has already been added to the import collection for source directory "%s".', $existItem->getNamespace(), $existItem->getSrc())
            );
        }

        $this->import[$finderFullyQualifiedName->getNamespace()] = $finderFullyQualifiedName;

        return $this;
    }

    public function get(): Iterator
    {
        yield from $this->import;
    }
}
