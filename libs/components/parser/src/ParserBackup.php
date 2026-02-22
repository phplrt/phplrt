<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Buffer\ArrayBuffer;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Source\SourceFactory;

final readonly class ParserBackup implements ParserInterface
{
    private SourceFactoryInterface $sources;

    public function __construct(
        private LexerInterface $lexer,
        /** @var array<int, RuleInterface> */
        private array $grammar,
        private int $initial,
        ?SourceFactoryInterface $sources = null,
    ) {
        $this->sources = $sources ?? SourceFactory::default();
    }

    public function parse(ReadableInterface $source): iterable
    {
        $readable = $this->sources->create($source);

        $buffer = new ArrayBuffer($this->filter($this->lexer->lex($readable)));

        $stack = new \SplStack();

        if ($this->reduce($this->grammar[$this->initial], $buffer, $stack)) {
            return $stack;
        }

        return [];
    }

    /**
     * @param iterable<TokenInterface> $tokens
     * @return \Traversable<TokenInterface>
     */
    private function filter(iterable $tokens): \Traversable
    {
        foreach ($tokens as $token) {
            if ($token->channel === Channel::DEFAULT) {
                yield $token;
            }
        }
    }

    private function reduce(RuleInterface $rule, ArrayBuffer $buffer, \SplStack $resultStack): bool
    {
        $stack = [[$rule, 0, $buffer->key(), $resultStack->count()]];
        $lastResult = null;

        while (\count($stack) > 0) {
            $index = \count($stack) - 1;
            [$currentRule, $step, $rollbackBuffer, $rollbackStackCount] = $stack[$index];

            if ($lastResult !== null) {
                // Обработка результата дочернего правила
                $result = $lastResult;
                $lastResult = null;

                if ($currentRule instanceof Concatenation) {
                    if (!$result) {
                        $buffer->seek($rollbackBuffer);
                        while ($resultStack->count() > $rollbackStackCount) {
                            $resultStack->pop();
                        }
                        $lastResult = false;
                        \array_pop($stack);
                        continue;
                    }

                    $nextStep = $step + 1;
                    if ($nextStep < \count($currentRule->ruleIds)) {
                        $stack[$index][1] = $nextStep;
                        $stack[] = [$this->grammar[$currentRule->ruleIds[$nextStep]], 0, $buffer->key(), $resultStack->count()];
                        continue;
                    }

                    $lastResult = true;
                    \array_pop($stack);
                    continue;
                }

                if ($currentRule instanceof Alternation) {
                    if ($result) {
                        $lastResult = true;
                        \array_pop($stack);
                        continue;
                    }

                    $nextStep = $step + 1;
                    if ($nextStep < \count($currentRule->ruleIds)) {
                        $stack[$index][1] = $nextStep;
                        $stack[] = [$this->grammar[$currentRule->ruleIds[$nextStep]], 0, $buffer->key(), $resultStack->count()];
                        continue;
                    }

                    $lastResult = false;
                    \array_pop($stack);
                    continue;
                }

                if ($currentRule instanceof Optional) {
                    $lastResult = true;
                    \array_pop($stack);
                    continue;
                }

                if ($currentRule instanceof Repetition) {
                    if (!$result) {
                        $buffer->seek($rollbackBuffer);
                        while ($resultStack->count() > $rollbackStackCount) {
                            $resultStack->pop();
                        }

                        $lastResult = $step >= $currentRule->gte;
                        \array_pop($stack);
                        continue;
                    }

                    $nextStep = $step + 1;
                    if ($nextStep < $currentRule->lte) {
                        $stack[$index][1] = $nextStep;
                        // Обновляем rollback points для новой итерации
                        $stack[$index][2] = $buffer->key();
                        $stack[$index][3] = $resultStack->count();
                        $stack[] = [$this->grammar[$currentRule->ruleId], 0, $buffer->key(), $resultStack->count()];
                        continue;
                    }

                    $lastResult = true;
                    \array_pop($stack);
                    continue;
                }
            }

            // Вход в правило (первый раз)
            if ($currentRule instanceof Lexeme) {
                $token = $buffer->current();

                if ($token->id === $currentRule->tokenId) {
                    $resultStack->push($token);
                    $buffer->next();
                    $lastResult = true;
                } else {
                    $lastResult = false;
                }
                \array_pop($stack);
                continue;
            }

            if ($currentRule instanceof Concatenation) {
                $stack[] = [$this->grammar[$currentRule->ruleIds[0]], 0, $buffer->key(), $resultStack->count()];
                continue;
            }

            if ($currentRule instanceof Alternation) {
                $stack[] = [$this->grammar[$currentRule->ruleIds[0]], 0, $buffer->key(), $resultStack->count()];
                continue;
            }

            if ($currentRule instanceof Optional) {
                $stack[] = [$this->grammar[$currentRule->ruleId], 0, $buffer->key(), $resultStack->count()];
                continue;
            }

            if ($currentRule instanceof Repetition) {
                if ($currentRule->lte > 0) {
                    $stack[] = [$this->grammar[$currentRule->ruleId], 0, $buffer->key(), $resultStack->count()];
                } else {
                    $lastResult = $currentRule->gte === 0;
                    \array_pop($stack);
                }
                continue;
            }

            $lastResult = false;
            \array_pop($stack);
        }

        return $lastResult;
    }
}
