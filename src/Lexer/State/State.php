<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\State;

/**
 * Class State
 */
abstract class State implements StateInterface
{
    /**
     * @var array|string[]
     */
    protected $tokens;

    /**
     * @var array|string[]
     */
    protected $skip;

    /**
     * @var array|string[]
     */
    protected $after;

    /**
     * @var array|string[]
     */
    protected $before;

    /**
     * State constructor.
     *
     * @param array|string[] $tokens
     * @param array|string[] $skip
     * @param array|string[] $after
     * @param array|string[] $before
     */
    public function __construct(array $tokens, array $skip = [], array $after = [], array $before = [])
    {
        $this->tokens = $tokens;
        $this->skip = $skip;
        $this->after = $after;
        $this->before = $before;
    }
}
