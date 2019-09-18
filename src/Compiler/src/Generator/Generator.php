<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Phplrt\Compiler\Analyzer;
use Phplrt\Parser\Rule\RuleInterface;

/**
 * Class Generator
 */
abstract class Generator implements GeneratorInterface
{
    /**
     * @var string|null
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $fqn;

    /**
     * @var Analyzer
     */
    protected $analyzer;

    /**
     * Generator constructor.
     *
     * @param Analyzer $analyzer
     * @param string $fqn
     */
    public function __construct(Analyzer $analyzer, string $fqn)
    {
        $this->analyzer = $analyzer;

        $this->bootFqn($fqn);
    }

    /**
     * @return array|RuleInterface[]
     */
    public function getRules(): array
    {
        $rules = $this->analyzer->rules;

        \uksort($rules, static function ($a, $b): int {
            if (\is_string($a) && \is_string($b)) {
                return $a <=> $b;
            }

            if (\is_string($a)) {
                return 1;
            }

            if (\is_string($b)) {
                return -1;
            }

            return $a <=> $b;
        });

        return $rules;
    }

    /**
     * @param string $fqn
     * @return void
     */
    private function bootFqn(string $fqn): void
    {
        $this->fqn = '\\' . \trim($fqn, '\\');

        $chunks          = \explode('\\', \trim($this->fqn, '\\'));
        $this->class     = \array_pop($chunks);
        $this->namespace = \implode('\\', $chunks) ?: null;
    }

    /**
     * @param string $class
     * @return string
     */
    protected function fqn(string $class): string
    {
        return '\\' . \ltrim($class, '\\');
    }

    /**
     * @param array|string[] $lines
     * @return string
     */
    protected function comment(array $lines): string
    {
        $result = [];

        foreach ($lines as $name => $value) {
            $value = \is_array($value) ? \implode('|', $value) : $value;

            $result[] = \is_string($name) ? \sprintf('@%s %s', $name, $value) : $value;
        }

        return $this->arrayToString($result);
    }

    /**
     * @param array $lines
     * @return string
     */
    protected function arrayToString(array $lines): string
    {
        return \trim(\implode("\n", $lines));
    }

    /**
     * @param string $name
     * @return string
     */
    protected function constantName(string $name): string
    {
        return \strtoupper($name);
    }
}
