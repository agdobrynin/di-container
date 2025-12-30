<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

interface CompiledEntryInterface
{
    /**
     * Compiled entry expression presented as string.
     *
     * Returned string must be a valid PHP expression without ending symbol `;`.
     *
     * It may be variable name `$service` or expression creating new class `new ClassName`, or present as
     * scalar value:
     *  - array with scalar values `[0 => 'str', 1 => ['foo' => 100, 'bar' => false]]`
     *  - string `'string'`
     *  - number `100`, `3.14`, `-200`
     *  - boolean `true`, `false`
     *  - Enumerations presented by `UnitEnum::class`
     */
    public function getExpression(): string;

    /**
     * Return PHP statements required to create a service.
     * Statements prepend compiled entry expression.
     *
     * Each statement must be ending with symbol `;`.
     *
     * Example of valid statements **string**:
     *
     *      '$service = new ServiceOne($this->get(\'service.two\'));
     *       $service = $service->withLogger($this->get(\'service.logger_file\'));'
     *
     * Statements must use service variable name in scope from
     * `\Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface::getScopeServiceVariableName()`
     *
     * If statements not required method return empty string.
     */
    public function getStatements(): string;

    /**
     * Return variable name for use in service scope when create service with statements.
     *
     * The variable name present as string and start with symbol `$` and be valid variable name in PHP.
     *
     * Valid value: `'$service'`, `'$object_123'`.
     */
    public function getScopeServiceVariableName(): string;

    /**
     * Return list of all variables using in compiled entry.
     *
     * All variables must start with symbol `$` and be valid variable name in PHP.
     *
     *      [
     *          '$obj1',
     *          '$service',
     *      ]
     *
     * @return list<non-empty-string>
     */
    public function getScopeVariables(): array;

    /**
     * Compiled container entity is singleton.
     * When value is `null` container entity resolve directly.
     */
    public function isSingleton(): ?bool;

    /**
     * Return type of compiled container entity.
     *
     * @return non-empty-string
     */
    public function getReturnType(): string;
}
