<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar\PP2Grammar;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Decorator;
use Phplrt\Lexer\Multistate;

/**
 * @psalm-type StateType = Lexer::STATE_*
 */
final class Lexer extends Decorator
{
    /**
     * @var StateType
     */
    private const STATE_PP2_GRAMMAR = 0;

    /**
     * @var StateType
     */
    private const STATE_PHP_INJECTION = 1;

    /**
     * @var array<StateType, array<string, StateType>>
     */
    private const LEXER_TRANSITIONS = [
        self::STATE_PP2_GRAMMAR   => [
            'T_PHP_OPEN' => self::STATE_PHP_INJECTION,
        ],
        self::STATE_PHP_INJECTION => [
            'T_PHP_CODE' => self::STATE_PP2_GRAMMAR,
        ],
    ];

    /**
     * @return LexerInterface
     */
    protected function boot(): LexerInterface
    {
        $states = [
            self::STATE_PP2_GRAMMAR   => new PP2Lexer(),
            self::STATE_PHP_INJECTION => new PP2PHPLexer(new PhpLexer()),
        ];

        return new Multistate($states, self::LEXER_TRANSITIONS);
    }
}
