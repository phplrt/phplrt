<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The lexical token that returns from stream.
 */
interface TokenInterface
{
    /**
     * Token name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Token position in bytes.
     *
     * @return int
     */
    public function getOffset(): int;

    /**
     * Returns the value of the captured subgroup.
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * The token value size in bytes.
     *
     * @return int
     */
    public function getBytes(): int;

    /**
     * The token value size in chars (multibyte encodings contain several bytes).
     *
     * @return int
     */
    public function getLength(): int;
}
