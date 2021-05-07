<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Config;

use Phplrt\Contracts\Buffer\BufferInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\BuilderInterface;

/**
 * @psalm-type StepReducer = callable(ContextInterface, callable): mixed
 */
interface ConfigInterface
{
    /**
     * @param array<string|int, RuleInterface> $rules
     * @return string|int
     */
    public function getInitialRule(array $rules);

    /**
     * @param iterable<TokenInterface> $tokens
     * @return BufferInterface
     */
    public function getBuffer(iterable $tokens): BufferInterface;

    /**
     * @return StepReducer|null
     */
    public function getStepReducer(): ?callable;

    /**
     * @return string
     */
    public function getEoiTokenName(): string;

    /**
     * @return BuilderInterface
     */
    public function getBuilder(): BuilderInterface;
}
