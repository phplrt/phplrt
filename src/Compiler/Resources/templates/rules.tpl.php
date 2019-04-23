<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

use Phplrt\Parser\Rule\Alternation;
use Phplrt\Parser\Rule\Concatenation;
use Phplrt\Parser\Rule\Repetition;
use Phplrt\Parser\Rule\Terminal;

$result = [];

foreach ($this->getGrammar()->getRules() as $rule) {
    $args = [];

    switch (true) {
        case $rule instanceof Terminal:
            $args = [
                $this->render($rule->getTokenName()),
                $this->render($rule->isKept()),
            ];
            break;

        case $rule instanceof Alternation:
            $args = [
                $this->render($rule->getChildren()),
                $this->render($rule->getNodeId()),
            ];
            break;

        case $rule instanceof Repetition:
            $args = [
                $this->render($rule->getMin()),
                $this->render($rule->getMax()),
                $this->render($rule->getChildren()),
                $this->render($rule->getNodeId()),
            ];
            break;

        case $rule instanceof Concatenation:
            $args = [
                $this->render($rule->getChildren()),
                $this->render($rule->getNodeId()),
            ];
            break;
    }

    $params = [
        \basename(\str_replace('\\', '/', \get_class($rule))),
        $this->render($rule->getName()),
        \implode(', ', $args),
    ];

    $result[] = $rule->getDefaultId() === null
        ? \vsprintf('new %s(%s, %s)', $params)
        : \vsprintf('(new %s(%s, %s))->setDefaultId(%s)', \array_merge($params, [
            $this->render($rule->getDefaultId()),
        ]));
}

return $result;
