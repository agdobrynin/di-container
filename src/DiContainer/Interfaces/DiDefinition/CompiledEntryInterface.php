<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface CompiledEntryInterface
{
    /**
     * TODO make description for this method.
     */
    public function getExpression(): string;

    /**
     * TODO make description for this method.
     */
    public function getStatements(): string;

    /**
     * TODO make description for this method.
     */
    public function getScopeServiceVariableName(): ?string;

    /**
     * TODO make description for this method.
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
