<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed add this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use PhpParser\Node\Name;
use Phplrt\Compiler\Analyzer;
use Zend\Code\Generator\ValueGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Phplrt\Contracts\Grammar\RuleInterface;
use Zend\Code\Generator\Exception\RuntimeException;

/**
 * Class ZendGenerator
 */
class ZendGenerator extends Generator
{
    /**
     * ZendGenerator constructor.
     *
     * @param Analyzer $analyzer
     */
    public function __construct(Analyzer $analyzer)
    {
        if (\count($analyzer->tokens) > 1) {
            throw new \LogicException('Multistate lexers is not supported by ' . static::class);
        }

        parent::__construct($analyzer);
    }

    /**
     * @return array|string[]
     */
    public function getTokens(): array
    {
        return $this->analyzer->tokens[Analyzer::STATE_DEFAULT];
    }

    /**
     * @param string $pathname
     * @param string $fqn
     * @return void
     * @throws \Exception
     */
    public function save(string $pathname, string $fqn): void
    {
        \file_put_contents($pathname, $this->generate($fqn));
    }

    /**
     * @param string $fqn
     * @return string
     */
    public function generate(string $fqn): string
    {
        \ob_start();
        \extract($this->unpackFqn($fqn), \EXTR_OVERWRITE);

        require __DIR__ . '/../../resources/template.tpl.php';

        return \ob_get_clean();
    }

    /**
     * @param string $fqn
     * @return string
     */
    public function generateGrammar(string $fqn): string
    {
        \ob_start();
        \extract($this->unpackFqn($fqn), \EXTR_OVERWRITE);

        require __DIR__ . '/../../resources/grammar.tpl.php';

        return \ob_get_clean();
    }

    /**
     * @param string $fqn
     * @return string
     */
    public function generateBuilder(string $fqn): string
    {
        \ob_start();
        \extract($this->unpackFqn($fqn), \EXTR_OVERWRITE);

        require __DIR__ . '/../../resources/builder.tpl.php';

        return \ob_get_clean();
    }


    /**
     * @param PropertyGenerator $property
     * @return ZendGenerator|$this
     * @throws RuntimeException
     */
    public function withProperty(PropertyGenerator $property): self
    {
        if ($property->isConst()) {
            $this->addConstant($property);
        } else {
            $this->addProperty($property);
        }

        return $this;
    }

    /**
     * @param PropertyGenerator $property
     * @return void
     * @throws RuntimeException
     */
    private function addConstant(PropertyGenerator $property): void
    {
        $reserved = \in_array(\strtoupper($property->getName()), ['LEXER_SKIPS', 'LEXER_TOKENS'], true);

        if ($reserved) {
            throw new \InvalidArgumentException('Constant name ' . $property->getName() . ' is reserved');
        }

        $this->constants[\strtoupper($property->getName())] = $property->generate();
    }

    /**
     * @param PropertyGenerator $property
     * @return void
     * @throws RuntimeException
     */
    private function addProperty(PropertyGenerator $property): void
    {
        $reserved = \in_array(\strtolower($property->getName()), ['reducers', 'lexer'], true);

        if ($reserved) {
            throw new \InvalidArgumentException('Property name ' . $property->getName() . ' is reserved');
        }

        $this->properties[\strtolower($property->getName())] = $property->generate();
    }

    /**
     * @param MethodGenerator $method
     * @return ZendGenerator|$this
     */
    public function withMethod(MethodGenerator $method): self
    {
        $reserved = \in_array(\strtolower($method->getName()), ['build', 'grammar', '__construct'], true);

        if ($reserved) {
            throw new \InvalidArgumentException('Method name ' . $method->getName() . ' is reserved');
        }

        $this->methods[\strtolower($method->getName())] = $method->generate();

        return $this;
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

        return 'new ' . $this->classNameHash(\get_class($rule)) . '(' . \implode(', ', $arguments) . ')';
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
        $type = ValueGenerator::TYPE_AUTO;

        return (new ValueGenerator($value, $type, $output))->generate();
    }

    /**
     * @param string $fqn
     * @return array
     */
    private function unpackFqn(string $fqn): array
    {
        $name = new Name($fqn);

        return [
            'fqn'       => $name->toString(),
            'class'     => \array_pop($name->parts),
            'namespace' => $name->toString(),
        ];
    }
}
