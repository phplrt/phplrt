<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Dumper;

use Phplrt\Contracts\Ast\LeafInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Ast\RuleInterface;
use Phplrt\Contracts\Dumper\DumperInterface;

/**
 * Class XmlDumper
 */
class XmlDumper implements DumperInterface
{
    /**
     * @var string
     */
    private const DEFAULT_DOM_INDENT_CHARS = '  ';

    /**
     * @var string
     */
    private const DEFAULT_CHARSET = 'UTF-8';

    /**
     * @var string
     */
    private const DEFAULT_XML_VERSION = '1.1';

    /**
     * @var string
     */
    protected $charset = self::DEFAULT_CHARSET;

    /**
     * @var string
     */
    protected $version = self::DEFAULT_XML_VERSION;

    /**
     * @var bool
     */
    protected $format = true;

    /**
     * @var int
     */
    protected $initialIndention = 0;

    /**
     * @var int
     */
    protected $indention = 4;

    /**
     * XmlDumper constructor.
     */
    public function __construct()
    {
        \assert(\class_exists(\DOMDocument::class));
    }

    /**
     * @param mixed|NodeInterface $node
     * @return string
     */
    public function dump($node): string
    {
        $root = $this->document();

        $result = $root->saveXML($this->render($root, $node));

        return $this->format($result);
    }

    /**
     * @return \DOMDocument
     */
    private function document(): \DOMDocument
    {
        $dom               = new \DOMDocument($this->version, $this->charset);
        $dom->formatOutput = $this->format;

        return $dom;
    }

    /**
     * @param \DOMDocument $root
     * @param mixed $node
     * @return \DOMElement
     */
    private function render(\DOMDocument $root, $node): \DOMElement
    {
        switch (true) {
            case $node instanceof RuleInterface:
                return $this->rule($root, $node);

            case $node instanceof LeafInterface:
                return $this->leaf($root, $node);

            default:
                return $this->custom($root, $node);
        }
    }

    /**
     * @param \DOMDocument $root
     * @param RuleInterface $node
     * @return \DOMElement
     */
    private function rule(\DOMDocument $root, RuleInterface $node): \DOMElement
    {
        $rule = $root->createElement($this->name($node));

        $this->withAttributes($rule, $node);

        foreach ($node->getChildren() as $child) {
            $rule->appendChild($this->render($root, $child));
        }

        return $rule;
    }

    /**
     * @param mixed $node
     * @return string
     */
    private function name($node): string
    {
        if ($node instanceof NodeInterface) {
            return $this->escape(\preg_replace('/\W+/u', '', $node->getName()));
        }

        $chunks = \explode('\\', \get_class($node));

        return $this->escape(\array_pop($chunks));
    }

    /**
     * @param string $value
     * @return string
     */
    private function escape(string $value): string
    {
        return \htmlspecialchars($value);
    }

    /**
     * @param \DOMElement $ctx
     * @param $node
     * @return void
     */
    private function withAttributes(\DOMElement $ctx, $node): void
    {
        if ($node instanceof NodeInterface) {
            $this->withAttribute($ctx, 'offset', $node->getOffset());

            return;
        }

        $modifiers = $this->getPropertyModifiers();

        foreach ((new \ReflectionObject($node))->getProperties($modifiers) as $property) {
            $property->setAccessible(true);
            $name = $property->getName();

            if ($name && $name[0] === "\0") {
                continue;
            }

            $this->withAttribute($ctx, $name, $property->getValue($node));
        }
    }

    /**
     * @param \DOMElement $element
     * @param string $name
     * @param $value
     */
    private function withAttribute(\DOMElement $element, string $name, $value): void
    {
        $element->setAttribute($name, $this->value($value));
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function value($value): string
    {
        switch (true) {
            case \is_scalar($value):
                return (string)$value;

            case \is_array($value):
                return 'array(' . \count($value) . ') { ... }';

            case \is_object($value):
                return \get_class($value) . '::class';

            default:
                return $this->inline(\print_r($value, true));
        }
    }

    /**
     * @param string $text
     * @return string
     */
    protected function inline(string $text): string
    {
        return \str_replace(["\n", "\r", "\t"], ['\n', '', '\t'], $text);
    }

    /**
     * @return int
     */
    private function getPropertyModifiers(): int
    {
        return \ReflectionProperty::IS_PUBLIC;
    }

    /**
     * @param \DOMDocument $root
     * @param LeafInterface $node
     * @return \DOMElement
     */
    private function leaf(\DOMDocument $root, LeafInterface $node): \DOMElement
    {
        $leaf = $root->createElement($this->name($node), $node->getValue());

        $this->withAttributes($leaf, $node);

        return $leaf;
    }

    /**
     * @param \DOMDocument $root
     * @param mixed $node
     * @return \DOMElement
     */
    private function custom(\DOMDocument $root, $node): \DOMElement
    {
        $element = $root->createElement($this->name($node));

        $this->withAttributes($element, $node);

        if (\is_iterable($node)) {
            foreach ($node as $child) {
                $element->appendChild($this->render($root, $child));
            }
        }

        return $element;
    }

    /**
     * @param string $xml
     * @return string
     */
    private function format(string $xml): string
    {
        return \preg_replace_callback('/^(\h*)(.*?)$/isum', function (array $matches) {
            [, $prefix, $code] = $matches;

            return $this->initialIndent() . $this->indent($prefix) . $code;
        }, $xml) ?? $xml;
    }

    /**
     * @return string
     */
    private function initialIndent(): string
    {
        return \str_repeat($this->prefix(), $this->initialIndention);
    }

    /**
     * @return string
     */
    private function prefix(): string
    {
        return \str_repeat(' ', $this->indention);
    }

    /**
     * @param string $indent
     * @return string
     */
    private function indent(string $indent): string
    {
        return \str_replace(self::DEFAULT_DOM_INDENT_CHARS, $this->prefix(), $indent);
    }
}
