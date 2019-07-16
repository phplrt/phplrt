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
     * Returns a token type if he is known.
     *
     * For example, to implement tokens of php "token_get_all()" function:
     *
     * <code>
     *  >>> "<?php if (false) { return true; }"
     *
     *  --------------------------
     *    Type      | Value
     *  --------------------------
     *    379       | "<?php "
     *    327       | "if"
     *    382       | " "
     *    null      | "("
     *    319       | "false"
     *    null      | ")"
     *    382       | " "
     *    null      | "{"
     *    348       | "return"
     *    382       | " "
     *    319       | "true"
     *    null      | ";"
     *    382       | " "
     *    null      | "}"
     *  --------------------------
     * </code>
     *
     * @return int|null
     */
    public function getType(): ?int;

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
