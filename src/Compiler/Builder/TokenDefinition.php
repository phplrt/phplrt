<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

/**
 * Class TokenDefinition
 */
class TokenDefinition
{
    /**
     * @var string|null
     */
    public $as;

    /**
     * @var string|null
     */
    public $state;

    /**
     * @var string
     */
    public $pattern;

    /**
     * @var bool
     */
    public $skip = false;

    /**
     * @var bool
     */
    public $global = false;

    /**
     * @var string|null
     */
    public $goesTo;

    /**
     * @var string|null
     */
    public $causes;

    /**
     * TokenDefinition constructor.
     *
     * @param string $pattern
     */
    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * @param bool $global
     * @return TokenDefinition|$this
     */
    public function global(bool $global = true): self
    {
        $this->global = $global;

        return $this;
    }

    /**
     * @param bool $skip
     * @return TokenDefinition|$this
     */
    public function skip(bool $skip = true): self
    {
        $this->skip = $skip;

        return $this;
    }

    /**
     * @param string $name
     * @return TokenDefinition|$this
     */
    public function state(string $name): self
    {
        $this->state = $name;

        return $this;
    }

    /**
     * @return TokenDefinition|$this
     */
    public function default(): self
    {
        return $this->state(LexerBuilder::DEFAULT_STATE);
    }

    /**
     * @param string|null $state
     * @return TokenDefinition
     */
    public function goesTo(?string $state): self
    {
        $this->goesTo = $state;

        return $this;
    }

    /**
     * @return TokenDefinition|$this
     */
    public function goesToDefaultState(): self
    {
        return $this->goesTo(LexerBuilder::DEFAULT_STATE);
    }

    /**
     * @param string|null $state
     * @return TokenDefinition|$this
     */
    public function causes(?string $state): self
    {
        $this->causes = $state;

        return $this;
    }

    /**
     * @return TokenDefinition|$this
     */
    public function causesDefaultState(): self
    {
        return $this->causes(LexerBuilder::DEFAULT_STATE);
    }

    /**
     * @param string $name
     * @return TokenDefinition|$this
     */
    public function as(string $name): self
    {
        $this->as = $name;

        return $this;
    }

    /**
     * @param string $pattern
     * @return TokenDefinition|$this
     */
    public function pattern(string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }
}
