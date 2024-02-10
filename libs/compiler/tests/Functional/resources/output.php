<?php

use Phplrt\Parser\Parser;

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
    'transitions' => [

    ],
    'grammar' => [
        1 => new \Phplrt\Parser\Grammar\Lexeme('d', true),
        2 => new \Phplrt\Parser\Grammar\Repetition(0, 1, INF),
        3 => new \Phplrt\Parser\Grammar\Concatenation([1, 2]),
        4 => new \Phplrt\Parser\Grammar\Lexeme('p', false),
        5 => new \Phplrt\Parser\Grammar\Lexeme('d', true),
        0 => new \Phplrt\Parser\Grammar\Concatenation([4, 5])
    ],
    'reducers' => [
        3 => function (\Phplrt\Parser\Context $ctx, $children) {
            $token = $ctx->getToken();
            $offset = $token->getOffset();
            dump($offset);

            foreach ($children as $child) {
                dump($child);
            }

            return $children;
        },
        0 => function (\Phplrt\Parser\Context $ctx, $children) {
            dump($children);
        }
    ]
];
