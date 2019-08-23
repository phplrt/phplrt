<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Parser\Exception\ParserException;

/**
 * Class Parser
 */
final class Parser extends AbstractParser
{
    /**
     * @var string
     */
    private const ERROR_EMPTY_GRAMMAR = 'Parser grammar is empty';

    /**
     * Parser constructor.
     *
     * @param LexerInterface $lexer
     * @param array|RuleInterface[] $rules
     * @throws ParserException
     */
    public function __construct(LexerInterface $lexer, array $rules)
    {
        parent::__construct($lexer);

        $this->rules = $rules;
        $this->bootInitialRule();
    }

    /**
     * @return void
     * @throws ParserException
     */
    private function bootInitialRule(): void
    {
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->rules as $id => $rule) {
            $this->initial = $id;

            return;
        }

        throw new ParserException(self::ERROR_EMPTY_GRAMMAR);
    }
}
