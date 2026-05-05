<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Closure;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;
use LogicException;
use ParseError;
use ReflectionFunction;
use RuntimeException;
use SplFileObject;

use function array_shift;
use function count;
use function dirname;
use function end;
use function explode;
use function function_exists;
use function implode;
use function is_array;
use function is_string;
use function md5;
use function sprintf;
use function str_starts_with;
use function strcasecmp;
use function strtolower;
use function substr;
use function token_get_all;
use function var_export;

use const PHP_INT_MAX;
use const T_AND_EQUAL;
use const T_AS;
use const T_COALESCE_EQUAL;
use const T_COMMENT;
use const T_CONCAT_EQUAL;
use const T_CONST;
use const T_DIV_EQUAL;
use const T_DOC_COMMENT;
use const T_FILE;
use const T_FUNCTION;
use const T_LINE;
use const T_MINUS_EQUAL;
use const T_MOD_EQUAL;
use const T_MUL_EQUAL;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NS_C;
use const T_OR_EQUAL;
use const T_PLUS_EQUAL;
use const T_POW_EQUAL;
use const T_SL_EQUAL;
use const T_SR_EQUAL;
use const T_STATIC;
use const T_STRING;
use const T_USE;
use const T_WHITESPACE;
use const T_XOR_EQUAL;

/**
 * @phpstan-type PhpTokens list<array{0: int, 1: string, 2: int}|string>
 * @phpstan-type ParsedNamespaces array<string, array{startLine?: non-negative-int, endLine?: non-negative-int, imports?: array<string, string>, aliases?: array<string, string>}>
 */
final class FinderClosureCode implements FinderClosureCodeInterface
{
    /**
     * @var array<string, array{tokens: PhpTokens, namespaces: ParsedNamespaces}>
     */
    private array $closureFileStruct = [];

    /**
     * @var array<non-empty-string, true>
     */
    private static array $flippedBuiltinTypes = [
        'bool' => true, 'int' => true, 'float' => true, 'string' => true,
        'array' => true, 'object' => true, 'resource' => true, 'never' => true,
        'void' => true, 'false' => true, 'true' => true, 'null' => true,
        'callable' => true, 'mixed' => true, 'iterable' => true, 'self' => true,
        'parent' => true, 'static' => true,
    ];

    /**
     * @var array<int, true>
     */
    private static array $flippedSkipTokenId = [
        T_DOC_COMMENT => true, T_WHITESPACE => true, T_COMMENT => true,
    ];

    /**
     * @var array<int, true>
     */
    private static array $flippedAssignmentOperators = [
        T_AND_EQUAL => true, T_COALESCE_EQUAL => true, T_CONCAT_EQUAL => true,
        T_DIV_EQUAL => true, T_MINUS_EQUAL => true, T_MOD_EQUAL => true,
        T_MUL_EQUAL => true, T_OR_EQUAL => true, T_PLUS_EQUAL => true,
        T_POW_EQUAL => true, T_SL_EQUAL => true, T_SR_EQUAL => true, T_XOR_EQUAL => true,
    ];

    /**
     * @var array<non-empty-string, true>
     */
    private static array $flippedTokenTextEndFunctions = [
        ',' => true, ')' => true, '}' => true, ';' => true, ']' => true,
    ];

    /**
     * @var array<int, true>
     */
    private static array $flippedTokenIdIgnoreConvertTokenStringToFQN = [
        T_OBJECT_OPERATOR => true, T_NULLSAFE_OBJECT_OPERATOR => true,
        T_DOUBLE_COLON => true, T_FUNCTION => true, T_CONST => true,
    ];

    /**
     * @var array<non-empty-string, true>
     */
    private static array $flippedTokenTextBreakConvertTokenStringToFQN = [
        '(' => true, ')' => true, '=' => true, ';' => true, ',' => true, '[' => true,
    ];

    /**
     * @var array<int, true>
     */
    private static array $flippedTokenIdUseTokenTextInUse = [
        T_STRING => true, T_NAME_QUALIFIED => true, T_NAME_FULLY_QUALIFIED => true,
    ];

    /**
     * @throws RuntimeException
     * @throws LogicException
     */
    public function getCode(Closure $function): string
    {
        $reflection = new ReflectionFunction($function);
        [
            'tokens' => $tokens,
            'namespaces' => $namespaces,
        ] = $this->initClosureFileStruct($reflection);

        $importAliases = $imports = [];
        $closureNamespace = '';

        foreach ($namespaces as $key => $namespace) {
            $imports = $namespace['imports'] ?? [];
            $importAliases = $namespace['aliases'] ?? [];
            $closureNamespace = $key;
            $startLine = $namespace['startLine'] ?? 0;
            $endLine = $namespace['endLine'] ?? PHP_INT_MAX;

            if ($startLine <= $reflection->getStartLine()
                && $endLine >= $reflection->getEndLine()) {
                break;
            }
        }

        $defaultPrefixNamespace = '\\';

        if ('' !== $closureNamespace) {
            $defaultPrefixNamespace .= $closureNamespace.'\\';
        }

        /** @var string $closureFileName */
        $closureFileName = $reflection->getFileName();

        $fnIsStatic = $ignoreConvertTStringToFQN = $isAnonymousClass = false;
        $fnLevel = $fnType = $anonymousClassLevel = $openRound = 0;
        $fnStack = $fnTokens = [];
        $t_dir = $t_file = $t_ns = null;
        $token_line = -1;

        for ($i = 0, $totalTokens = count($tokens); $i < $totalTokens; ++$i) {
            $token = &$tokens[$i];

            /**
             * @var int    $token_id
             * @var string $token_text
             */
            [$token_id, $token_text, $token_line] = is_array($token)
                ? [$token[0], $token[1], $token[2]]
                : [null, $token, $token_line];

            if ($token_line < $reflection->getStartLine()) {
                continue;
            }

            if (0 === $fnType && T_STATIC === $token_id) {
                $fnIsStatic = true;
                $fnTokens[] = $token_text.' ';
            }

            if (0 === $fnLevel && (T_FN === $token_id || T_FUNCTION === $token_id)) {
                if (!$fnIsStatic) {
                    throw new LogicException(
                        sprintf('Anonymous function must be declared as static via keyword "static". Code from file "%s" at line %d.', $closureFileName, $token_line)
                    );
                }

                $fnType = $token_id;
                $fnTokens[] = $token_text;

                continue;
            }

            if (0 !== $fnType && 0 === $fnLevel && T_USE === $token_id) {
                throw new LogicException(
                    sprintf('Anonymous function cannot use a reference variable via keyword "use". Code from file "%s" at line %d.', $closureFileName, $token_line),
                );
            }

            if (0 !== $fnType) {
                if (0 === $fnLevel && isset(self::$flippedTokenTextEndFunctions[$token_text])) {
                    break;
                }

                $openRound = match ($token_text) {
                    '(' => $openRound + 1,
                    ')' => $openRound - 1,
                    default => $openRound
                };

                if (isset(self::$flippedTokenIdIgnoreConvertTokenStringToFQN[$token_id])) {
                    $ignoreConvertTStringToFQN = true;
                    $fnTokens[] = $token_text;

                    continue;
                }

                // char brake ignoring convert T_STRING to FQN.
                if ($ignoreConvertTStringToFQN
                    && (
                        isset(self::$flippedAssignmentOperators[$token_id])
                        || isset(self::$flippedTokenTextBreakConvertTokenStringToFQN[$token_text])
                    )
                ) {
                    $ignoreConvertTStringToFQN = false;
                }

                // check using $this in closure
                if (0 === $anonymousClassLevel && T_VARIABLE === $token_id && 0 === strcasecmp('$this', $token_text)) {
                    throw new LogicException(
                        sprintf('Anonymous arrow function cannot use a reference variable via "$this". Code from file "%s" at line %d.', $closureFileName, $token_line)
                    );
                }

                if (T_CLASS === $token_id) {
                    $isAnonymousClass = true;
                }

                if ($isAnonymousClass && 0 === match ($token_text) {
                    '{' => ++$anonymousClassLevel, '}' => --$anonymousClassLevel, default => null
                }) {
                    $isAnonymousClass = false;
                    $anonymousClassLevel = 0;
                }

                // check self, parent, static in closure.
                if (0 === $anonymousClassLevel) {
                    if (T_STRING === $token_id
                        && (0 === strcasecmp('self', $token_text) || 0 === strcasecmp('parent', $token_text))) {
                        throw new LogicException(
                            sprintf('Anonymous function cannot use a reference variable via keyword "%s". Code from file "%s" at line %d.', $token_text, $closureFileName, $token_line)
                        );
                    }

                    if (T_STATIC === $token_id) {
                        throw new LogicException(
                            sprintf('Anonymous function cannot use a reference variable via keyword "%s". Code from file "%s" at line %d.', $token_text, $closureFileName, $token_line)
                        );
                    }
                }

                if (T_STRING === $token_id && !$ignoreConvertTStringToFQN) {
                    $token_key = strtolower($token_text);
                    if (!isset(self::$flippedBuiltinTypes[$token_key])) {
                        if ($openRound > 0) {
                            for ($j = $i + 1; $j < $totalTokens; ++$j) {
                                [$inside_round_token_id, $inside_round_token_text] = is_array($tokens[$j])
                                    ? [$tokens[$j][0], $tokens[$j][1]]
                                    : [0, $tokens[$j]];

                                if (isset(self::$flippedSkipTokenId[$inside_round_token_id])) {
                                    continue;
                                }

                                if (':' === $inside_round_token_text) {
                                    $fnTokens[] = $token_text.':';

                                    $i = $j;

                                    continue 2;
                                }

                                if ($inside_round_token_id > 0
                                    || '(' === $tokens[$j]
                                    || ',' === $tokens[$j]
                                    || ')' === $tokens[$j]) {
                                    break;
                                }
                            }
                        }

                        $fnTokens[] = match (true) {
                            isset($importAliases[$token_key]) => $importAliases[$token_key],
                            isset($imports[$token_key]) => $imports[$token_key],
                            default => function_exists($token_text)
                                ? '\\'.$token_text
                                : $defaultPrefixNamespace.$token_text
                        };

                        continue;
                    }
                } elseif (T_NAME_QUALIFIED === $token_id) {
                    $qualified_parts = explode('\\', $token_text);
                    $first_qualified = array_shift($qualified_parts);
                    $token_key = strtolower($first_qualified);
                    $fnTokens[] = ($importAliases[$token_key] ?? $imports[$token_key] ?? $defaultPrefixNamespace.$first_qualified)
                        .'\\'.implode('\\', $qualified_parts);

                    continue;
                }

                $magicConstStr = match ($token_id) {
                    T_DIR => $t_dir ??= var_export(dirname($closureFileName), true),
                    T_FILE => $t_file ??= var_export($closureFileName, true),
                    T_LINE => var_export($token_line, true),
                    T_NS_C => $t_ns ??= var_export(
                        '\\' !== ($defaultPrefixNamespace) ? substr($defaultPrefixNamespace, 0, -1) : '',
                        true
                    ),
                    default => false
                };

                if (is_string($magicConstStr)) {
                    $fnTokens[] = $magicConstStr;

                    continue;
                }

                $fnTokens[] = $token_text;

                if ('{' === $token_text) {
                    $fnStack[$fnLevel++] = '}';
                } elseif ('(' === $token_text) {
                    $fnStack[$fnLevel++] = ')';
                } elseif ('[' === $token_text) {
                    $fnStack[$fnLevel++] = ']';
                } elseif ($fnLevel > 0 && $fnStack[$fnLevel - 1] === $token_text) {
                    --$fnLevel;
                    if (0 === $fnLevel && '}' === $token_text) {
                        break;
                    }
                }
            }
        }

        return implode($fnTokens);
    }

    /**
     * @return array{tokens: PhpTokens, namespaces: ParsedNamespaces}
     *
     * @throws RuntimeException
     */
    private function initClosureFileStruct(ReflectionFunction $reflectionFunction): array
    {
        if (false === $reflectionFunction->getFileName()) {
            throw new RuntimeException('Function defined in the PHP core or in a PHP extension.');
        }

        $fileName = $reflectionFunction->getFileName();
        $fileKey = md5($fileName);

        if (isset($this->closureFileStruct[$fileKey])) {
            return $this->closureFileStruct[$fileKey];
        }

        try {
            $f = (new SplFileObject($fileName))->openFile('rb');
            $code = '';

            while (!$f->eof()) {
                $code .= $f->fread(8192);
            }

            /** @var PhpTokens $tokens */
            $tokens = token_get_all($code, TOKEN_PARSE);
        } catch (LogicException|ParseError|RuntimeException  $e) {
            throw new RuntimeException(
                sprintf('Cannot parse code from file "%s". Reason: %s', $fileName, $e->getMessage()),
                previous: $e
            );
        }

        $useNamespaceLevel = $namespaceBraceLevel = $lastFoundLine = 0;
        $isUseStart = $isAlias = $isNamespace = $isNamespaceBrace = $isNamespaceDetected = false;
        $namespace = '';

        /**
         * @var ParsedNamespaces $namespaces
         */
        $namespaces = [];
        $namespaces[$namespace] = [
            'startLine' => 0,
            'endLine' => PHP_INT_MAX,
        ];

        /** @var array{0: list<string>, 1?: list<string>} $use */
        $use = [];

        foreach ($tokens as $token) {
            if (is_array($token)) {
                /**
                 * @var int              $token_id
                 * @var string           $token_text
                 * @var non-negative-int $lastFoundLine
                 */
                [$token_id, $token_text, $lastFoundLine] = [$token[0], $token[1], $token[2]];
            } else {
                $token_id = null;
                $token_text = $token;
            }

            if (T_NAMESPACE === $token_id) {
                $isNamespace = true;

                if (isset($namespaces[$namespace])) {
                    $namespaces[$namespace]['endLine'] = $lastFoundLine > 0 ? $lastFoundLine - 1 : $lastFoundLine;
                }
            }

            if ($isNamespace && (T_STRING === $token_id || T_NAME_QUALIFIED === $token_id)) {
                $isNamespaceDetected = true;
                $namespace = $token_text;
                $namespaces[$namespace] = [
                    'startLine' => $lastFoundLine,
                    'endLine' => PHP_INT_MAX,
                ];

                continue;
            }

            if ($isNamespace && ('{' === $token_text || ';' === $token_text)) {
                $isNamespace = false;

                if (false === $isNamespaceDetected) {
                    $namespace = '';
                    $namespaces[$namespace]['startLine'] = $lastFoundLine;
                } else {
                    $isNamespaceDetected = false;
                }

                if ('{' === $token_text) {
                    $isNamespaceBrace = true;
                }
            }

            if ($isNamespaceBrace && 0 === match ($token_text) {
                '{', => ++$namespaceBraceLevel,
                '}' => --$namespaceBraceLevel,
                default => null,
            }) {
                $isNamespaceBrace = false;
                $namespaceBraceLevel = 0;

                if (isset($namespaces[$namespace])) {
                    $namespaces[$namespace]['endLine'] = $lastFoundLine;
                }
            }

            if (T_USE === $token_id) {
                $isUseStart = true;
                $useNamespaceLevel = 0;
                $isAlias = false;
                $use = [];

                continue;
            }

            if ($isUseStart) {
                if (';' === $token_text && isset($use[0])) { // end import
                    $foundImports = [];
                    [$prefixFoundUses, $foundUses] = isset($use[1])
                        ? [$use[0][0].'\\', $use[1]]
                        : ['', $use[0]];

                    foreach ($foundUses as $u) {
                        $fullUseItem = str_starts_with($useItem = $prefixFoundUses.$u, '\\')
                            ? $useItem
                            : '\\'.$useItem;

                        $partsOfUse = explode('\\', $fullUseItem);
                        $foundImports[strtolower(end($partsOfUse))] = $fullUseItem;
                    }

                    if (isset($namespaces[$namespace]['imports'])) {
                        $namespaces[$namespace]['imports'] += $foundImports;
                    } else {
                        $namespaces[$namespace]['imports'] = $foundImports;
                    }

                    $isUseStart = false;

                    continue;
                }

                if ($isAlias && ',' === $token_text) {
                    $isAlias = false;

                    continue;
                }

                if ('{' === $token_text || '}' === $token_text) {
                    '{' === $token_text
                        ? ++$useNamespaceLevel
                        : --$useNamespaceLevel;

                    continue;
                }

                if (T_AS === $token_id) {
                    $isAlias = true;

                    continue;
                }

                if ($isAlias && T_STRING === $token_id) {
                    /** @var string $fullUseOfAlias */
                    $fullUseOfAlias = isset($use[1])
                        ? $use[0][0].'\\'.end($use[1])
                        : $use[0][0];

                    $namespaces[$namespace]['aliases'][strtolower($token_text)] = str_starts_with($fullUseOfAlias, '\\')
                        ? $fullUseOfAlias
                        : '\\'.$fullUseOfAlias;

                    continue;
                }

                if (false === $isAlias && null !== $token_id && isset(self::$flippedTokenIdUseTokenTextInUse[$token_id])) {
                    $use[$useNamespaceLevel][] = $token_text;
                }
            }
        }

        return $this->closureFileStruct[$fileKey] = [
            'tokens' => $tokens,
            'namespaces' => $namespaces,
        ];
    }
}
