<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ProxyClosureEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\TaggedAsEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;

use function gettype;
use function sprintf;

final class DiDefinitionTransformer implements DiDefinitionTransformerInterface
{
    public function __construct(private readonly FinderClosureCodeInterface $closureParser) {}

    public function transform(mixed $definition, DiContainerInterface $container): CompilableDefinitionInterface
    {
        if ($definition instanceof DiDefinitionValue) {
            return new ValueEntry($definition->getDefinition());
        }

        if ($definition instanceof DiDefinitionLinkInterface) {
            return new GetEntry($definition);
        }

        if ($definition instanceof DiDefinitionTaggedAsInterface) {
            return new TaggedAsEntry($definition, $container);
        }

        if ($definition instanceof DiDefinitionProxyClosure) {
            return new ProxyClosureEntry($definition, $container);
        }

        throw new DefinitionCompileException(sprintf('Unsupported definition type "%s"', gettype($definition)));
    }

    public function getClosureParser(): FinderClosureCodeInterface
    {
        return $this->closureParser;
    }
}
