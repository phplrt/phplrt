<?php

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Renderer\RendererInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

class Generator
{
    /**
     * @var Analyzer
     */
    protected Analyzer $analyzer;

    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;

    /**
     * @var array<class-string, non-empty-string|null>
     */
    private array $declarations = [];

    /**
     * @param Analyzer $analyzer
     * @param RendererInterface $renderer
     */
    public function __construct(Analyzer $analyzer, RendererInterface $renderer)
    {
        $this->analyzer = $analyzer;
        $this->renderer = $renderer;
    }

    /**
     * @param class-string $class
     * @param non-empty-string|null $alias
     * @return $this
     */
    public function withClassUsage(string $class, string $alias = null): self
    {
        $class = \trim($class, '\\');

        $this->declarations[$class] = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->generate();
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        $result = $this->renderer->fromString([
            'initial'     => $this->renderer->fromPhp($this->analyzer->initial, 1),
            'tokens'      => $this->renderer->fromPhp($this->analyzer->tokens, 1),
            'skip'        => $this->renderer->fromPhp($this->analyzer->skip, 1),
            'transitions' => $this->renderer->fromPhp($this->analyzer->transitions, 1),
            'grammar'     => $this->renderer->fromString($this->getRules(1), 1),
            'reducers'    => $this->renderer->fromString($this->getReducers(1), 1),
        ]);

        return \implode("\n", [
            '<?php',
            ...\array_values($this->getUses()),
            sprintf('return %s;', $result),
        ]);
    }

    /**
     * @return array<string>
     */
    private function getUses(): array
    {
        $usages = [];

        foreach ($this->declarations as $class => $alias) {
            $class = \trim($class, '\\');
            $usages[] = $alias
                ? \sprintf('use %s as %s;', $class, $alias)
                : \sprintf('use %s;', $class);
        }

        if ($usages !== []) {
            \array_unshift($usages, '');
            $usages[] = '';
        }

        return $usages;
    }

    /**
     * @param int<0, max> $depth
     * @return array|string[]
     */
    private function getRules(int $depth): array
    {
        $map = fn (RuleInterface $rule): string => $this->getRuleAsString($rule, $depth);

        return \array_map($map, $this->analyzer->rules);
    }

    /**
     * @param RuleInterface $rule
     * @param int<0, max> $depth
     * @return string
     */
    private function getRuleAsString(RuleInterface $rule, int $depth): string
    {
        switch (true) {
            case $rule instanceof Alternation:
            case $rule instanceof Concatenation:
                return $this->newRule($depth, $rule, [$rule->sequence]);

            case $rule instanceof Lexeme:
                return $this->newRule($depth, $rule, [$rule->token, $rule->keep]);

            case $rule instanceof Optional:
                return $this->newRule($depth, $rule, [$rule->rule]);

            case $rule instanceof Repetition:
                return $this->newRule($depth, $rule, [$rule->rule, $rule->gte, $rule->lte]);
        }

        return $this->newRule($depth, $rule, []);
    }

    /**
     * @param int<0, max> $depth
     * @param RuleInterface $rule
     * @param array $args
     * @return string
     */
    private function newRule(int $depth, RuleInterface $rule, array $args): string
    {
        $args = \array_map(function ($arg) use ($depth) {
            return $this->renderer->fromPhp($arg, $depth, false);
        }, $args);

        return 'new \\' . \get_class($rule) . '(' . \implode(', ', $args) . ')';
    }

    /**
     * @param int<0, max> $depth
     * @return array
     */
    private function getReducers(int $depth): array
    {
        return \array_map(function (string $code) use ($depth): string {
            $code = \str_replace("\r", '', \trim($code));

            return $this->toFunction($code, $depth);
        }, $this->analyzer->reducers);
    }

    /**
     * @param string $code
     * @param int<0, max> $depth
     * @return string
     */
    private function toFunction(string $code, int $depth): string
    {
        $code = \implode("\n", $this->format(\explode("\n", $code), $depth));

        $suffix = $this->renderer->prefix($depth + 1);

        $class = Context::class;

        return "function (\\{$class} \$ctx, \$children) {\n{$code}\n{$suffix}}";
    }

    /**
     * @param array<string> $lines
     * @param int<0, max> $depth
     * @return array<string>
     */
    private function format(array $lines, int $depth): array
    {
        $prefix = $this->renderer->prefix($depth + 1);

        $lines[0] = $prefix . $lines[0] ?? '';

        $lines = $this->addInjections($lines, [
            'file'   => $prefix . '$file = $ctx->getSource();',
            'source' => $prefix . '$source = $ctx->getSource();',
            'offset' => $prefix . '$offset = $token->getOffset();',
            'token'  => $prefix . '$token = $ctx->getToken();',
            'state'  => $prefix . '$state = $ctx->getState();',
            'rule'   => $prefix . '$rule = $ctx->getRule();',
        ]);

        return \array_map(function (string $line) use ($depth): string {
            return $this->renderer->prefix($depth) . $line;
        }, $lines);
    }

    /**
     * @param array<string> $lines
     * @param array<non-empty-string, string> $needles
     * @return array<string>
     */
    private function addInjections(array $lines, array $needles): array
    {
        $append = [];

        foreach ($needles as $variable => $needle) {
            if ($this->has($lines, $variable) || $this->has($append, $variable)) {
                \array_unshift($append, $needle);
            }
        }

        return [...$append, ...\array_values($lines)];
    }

    /**
     * @param array<string> $lines
     * @param non-empty-string $variable
     * @return bool
     */
    private function has(array $lines, string $variable): bool
    {
        $pattern = \sprintf('/\$%s\b/isum', \preg_quote($variable, '/'));

        foreach ($lines as $line) {
            if (\preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }
}
