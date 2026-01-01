<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;

interface CompiledEntryInterface
{
    /**
     * Compiled entry expression presented as string.
     *
     * Returned string must be a valid PHP expression without ending symbol `;`.
     */
    public function getExpression(): string;

    /**
     * Compiled entry expression presented as string.
     *
     * Expression string must be a valid PHP expression without ending symbol `;`.
     *
     * Expression may be:
     *  - variable name `$service`
     *  - expression creating object with syntaxes `new ClassName`
     *  - callable string `[ClassName::class, 'staticMethod']`.
     *
     * Also, expression maybe present as scalar value:
     *   - array with scalar values `[0 => 'str', 1 => ['foo' => 100, 'bar' => false]]`
     *   - string `'string'`
     *   - number `100`, `3.14`, `-200`
     *   - boolean `true`, `false`
     *   - Enumerations presented by `UnitEnum::class`
     *
     * @return $this
     */
    public function setExpression(string $expression): static;

    /**
     * Return PHP statements required to create a service.
     * Statements prepend compiled entry expression.
     * Each expression string in statements must be ending without symbol `;`.
     *
     * Valid statements:
     *
     *      [
     *          0 => '$service = new ServiceOne($this->get(\'service.two\'))',
     *          1 => '$service = $service->withLogger($this->get(\'service.logger_file\'))',
     *      ]
     *
     * Statements must use service variable name in scope from
     * `\Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface::getScopeServiceVar()`
     *
     * @return list<non-empty-string>
     */
    public function getStatements(): array;

    /**
     * Add PHP expression need to create service.
     * Each expression string must be ending without symbol `;`.
     *
     * Valid expression for statements:
     *
     *      '$service = $service->withLogger($this->get(\'service.logger_file\'))'
     *
     * @param non-empty-string ...$expression
     *
     * @return $this
     */
    public function addToStatements(string ...$expression): static;

    /**
     * Return variable name using for create service object.
     *
     * The variable name present as string and start with symbol `$` and be valid variable name in PHP.
     *
     * Valid value: `'$service'`, `'$object_123'`.
     *
     * @return non-empty-string
     */
    public function getScopeServiceVar(): string;

    /**
     * Return list of all variables using in compiled entry.
     *
     * Example valid names:
     *
     *      [
     *          0 => '$obj1',
     *          1 => '$service',
     *      ]
     *
     * @return list<non-empty-string>
     */
    public function getScopeVars(): array;

    /**
     * Add variable name to service scope variables.
     * Variable name must start with symbol `$` and be valid variable name in PHP.
     *
     * Example valid name `'$service'`.
     *
     * @param non-empty-string $name
     *
     * @return $this
     *
     * @throws DefinitionCompileExceptionInterface
     */
    public function addToScopeVars(string ...$name): static;

    /**
     * Get singleton flag.
     *
     * When value is `null` container entity resolve directly.
     */
    public function isSingleton(): ?bool;

    /**
     * Set the singleton flag.
     *
     * @return $this
     */
    public function setIsSingleton(?bool $isSingleton): static;

    /**
     * Get return type of compiled container entity.
     *
     * @return non-empty-string
     */
    public function getReturnType(): string;

    /**
     * Set return type of compiled container entity.
     *
     * @param non-empty-string $returnType
     *
     * @return $this
     */
    public function setReturnType(string $returnType): static;
}
