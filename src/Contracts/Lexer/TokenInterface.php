<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The lexical token that returns from LexerInterface
 */
interface TokenInterface
{
    /**
     * Returns the ID or name of the token.
     *
     * Please note that the token can be anonymous. In this case, the name
     * should return the same as the contents of the token, index or null.
     *
     * For example, to implement tokens of php "token_get_all()" function:
     *
     * <code>
     *  // Source: "<?php if (false) { return true; }"
     *
     *  ------------------------------
     *    Name          | Value
     *  ------------------------------
     *    T_OPEN_TAG    | "<?php "
     *    T_IF          | "if"
     *    T_WHITESPACE  | " "
     *    null          | "("
     *    T_STRING      | "false"
     *    null          | ")"
     *    T_WHITESPACE  | " "
     *    null          | "{"
     *    T_RETURN      | "return"
     *    T_WHITESPACE  | " "
     *    T_STRING      | "true"
     *    null          | ";"
     *    T_WHITESPACE  | " "
     *    null          | "}"
     *  ------------------------------
     * </code>
     *
     * @return string|int|null
     */
    public function getName();

    /**
     * Token position in bytes
     *
     * @return int
     */
    public function getOffset(): int;

    /**
     * Returns the value of the captured subgroup
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * The token value size in bytes
     *
     * @return int
     */
    public function getBytes(): int;
}
