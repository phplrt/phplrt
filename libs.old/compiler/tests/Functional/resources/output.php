<?php

declare(strict_types=1);

use Phplrt\Parser\Parser;

/**
 * @var array{
 *     initial: array-key,
 *     tokens: array{
 *         default: array<non-empty-string, non-empty-string>,
 *         ...
 *     },
 *     skip: list<non-empty-string>,
 *     grammar: array<array-key, \Phplrt\Parser\Grammar\RuleInterface>,
 *     reducers: array<array-key, callable(\Phplrt\Parser\Context, mixed):mixed>,
 *     transitions?: array<array-key, mixed>
 * }
 */
return [
    'initial' => 3,
    'tokens' => [
        'default' => [
            'd' => '\\d+',
            'p' => '\\+',
            'ws' => '\\s+',
        ],
    ],
    'skip' => [
        'ws',
    ],
    'transitions' => [],
    'grammar' => [
        new \Phplrt\Parser\Grammar\Concatenation([4, 5]),
        new \Phplrt\Parser\Grammar\Lexeme('d', true),
        new \Phplrt\Parser\Grammar\Repetition(0, 1, INF),
        new \Phplrt\Parser\Grammar\Concatenation([1, 2]),
        new \Phplrt\Parser\Grammar\Lexeme('p', false),
        new \Phplrt\Parser\Grammar\Lexeme('d', true),
    ],
    'reducers' => [
        0 => static function (\Phplrt\Parser\Context $ctx, $children): void {
            dump($children);
        },
        3 => static function (\Phplrt\Parser\Context $ctx, $children) {
            // The "$offset" variable is an auto-generated
            $offset = $ctx->lastProcessedToken->getOffset();

            dump($offset);

            foreach ($children as $child) {
                dump($child);
            }

            return $children;
        },
    ],
];
