<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed add this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Phplrt\Compiler\Analyzer;
use Phplrt\Parser\Rule\RuleInterface;
use Zend\Code\Generator\Exception\RuntimeException;
use Zend\Code\Generator\ValueGenerator;

/**
 * Class ZendGenerator
 */
class ZendGenerator extends Generator
{
    /**
     * ZendGenerator constructor.
     *
     * @param Analyzer $analyzer
     * @param string $fqn
     */
    public function __construct(Analyzer $analyzer, string $fqn)
    {
        if (\count($analyzer->tokens) > 1) {
            throw new \LogicException('Multistate lexers is not supported by ' . static::class);
        }

        parent::__construct($analyzer, $fqn);
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return $this->analyzer->tokens[Analyzer::STATE_DEFAULT];
    }

    /**
     * @param string $pathname
     * @return void
     * @throws \Exception
     */
    public function save(string $pathname): void
    {
        \file_put_contents($pathname, $this->generate());
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function generate(): string
    {
        \ob_start();

        require __DIR__ . '/../Resources/template.tpl.php';

        return \ob_get_clean();
    }

    /**
     * @param mixed $value
     * @param bool $multiline
     * @return string
     * @throws RuntimeException
     */
    public function value($value, bool $multiline = true): string
    {
        $output = $multiline ? ValueGenerator::OUTPUT_MULTIPLE_LINE : ValueGenerator::OUTPUT_SINGLE_LINE;
        $type   = ValueGenerator::TYPE_AUTO;

        return (new ValueGenerator($value, $type, $output))->generate();
    }

    /**
     * @param RuleInterface $rule
     * @return string
     * @throws RuntimeException
     */
    protected function rule(RuleInterface $rule): string
    {
        $arguments = [];

        foreach ($rule->getConstructorArguments() as $argument) {
            $arguments[] = $this->value($argument, false);
        }

        return 'new \\' . \get_class($rule) . '(' . \implode(', ', $arguments) . ')';
    }
}
