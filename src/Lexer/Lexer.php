<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Lexer\State\StateInterface;

/**
 * Class Lexer
 */
class Lexer extends AbstractLexer
{
    /**
     * Lexer constructor.
     *
     * @param array|StateInterface[]|array[] $states
     * @param null|int $initial
     */
    public function __construct(array $states = [], int $initial = null)
    {
        $this->initial = $initial;
        /** @noinspection AdditionOperationOnArraysInspection */
        $this->states = $states;

        parent::__construct();
    }

    /**
     * @param array $tokens
     * @param array $skip
     * @return Lexer|$this
     */
    public static function create(array $tokens, array $skip = []): self
    {
        return new static([
            [
                $tokens,
                $skip,
            ],
        ]);
    }
}
