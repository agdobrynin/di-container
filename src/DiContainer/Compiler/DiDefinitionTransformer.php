<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Compiler\DefinitionCompiler\Get;
use Kaspi\DiContainer\Compiler\DefinitionCompiler\Value;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;

use function gettype;
use function sprintf;

final class DiDefinitionTransformer implements DiDefinitionTransformerInterface
{
    public function transform(mixed $definition): CompilableDefinitionInterface
    {
        if ($definition instanceof DiDefinitionValue) {
            return new Value($definition->getDefinition());
        }

        if ($definition instanceof DiDefinitionLinkInterface) {
            return new Get($definition);
        }

        throw new DefinitionCompileException(sprintf('Unsupported definition type "%s"', gettype($definition)));
    }
}
