<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Source\ReadableInterface;
use Psr\Http\Message\StreamInterface;

/**
 * An interface that implements methods for parsing source code.
 *
 * @psalm-type SourceType = (ReadableInterface|StreamInterface|\SplFileInfo|string|resource)
 *
 * @see StreamInterface
 * @see ReadableInterface
 */
interface ParserInterface
{
    /**
     * Parses the source and returns the result of the parsing.
     *
     * In most cases, result can be an abstract syntax tree (AST). However, in
     * some cases, the parser implementation can execute the code and return
     * the computation result.
     *
     * Declarative parser's example result:
     * <code>
     *  $result = $parser->parse('{ key: value }');
     *  // object(ObjectNode) {
     *  //   key: object(KeyNode) {
     *  //     name: "key"
     *  //   },
     *  //   value: object(ValueNode) {
     *  //     value: "value"
     *  //   }
     *  // }
     * </code>
     *
     * Imperative parser's example result:
     * <code>
     *  $result = $parser->parse('2 + 2');
     *  // 4
     * </code>
     *
     * @param mixed $source
     * @psalm-param SourceType $source
     * @return mixed
     */
    public function parse(mixed $source): mixed;
}
