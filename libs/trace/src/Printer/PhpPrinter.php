<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Trace\Printer;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Trace\FunctionInvocationInterface;
use Phplrt\Contracts\Trace\InvocationInterface;
use Phplrt\Contracts\Trace\MethodInvocationInterface;
use Phplrt\Contracts\Trace\TraceInterface;
use Phplrt\SourceMap\EntryInterface;

/**
 * Reference implementation of Zend Trace Printer
 *
 * @link https://github.com/php/php-src/blob/php-8.1.0/Zend/zend_exceptions.c#L491
 * @link https://github.com/php/php-src/blob/php-8.1.0/Zend/zend_smart_str.c#L193
 */
class PhpPrinter implements PrinterInterface
{
    /**
     * @param PhpPrinterCreateInfo $info
     */
    public function __construct(
        private readonly PhpPrinterCreateInfo $info = new PhpPrinterCreateInfo()
    ) {
    }

    /**
     * @param TraceInterface $trace
     * @return string
     */
    public function print(TraceInterface $trace): string
    {
        $result = [];
        $index = 0;

        /** @var EntryInterface $item */
        foreach ($trace as $index => $item) {
            $result[] = $this->file($index, $item) . $this->info->delimiter . $this->entryToString($item);
        }

        $result[] = $this->info->index
            ? '#' . ($index + 1) . ' {main}'
            : '{main}';

        return \implode($this->info->eol, $result);
    }

    /**
     * @param positive-int|0 $index
     * @param InvocationInterface $entry
     * @return non-empty-string
     */
    private function file(int $index, InvocationInterface $entry): string
    {
        $source = $entry->getSource();

        $name = $source instanceof FileInterface
            ? $source->getPathname()
            : '[internal function]'
            ;

        $result = '';

        if ($this->info->index) {
            $result .= "#$index ";
        }

        $result .= $name;

        if ($source instanceof FileInterface) {
            $result .= '(' . $entry->getLine();

            if ($this->info->columns) {
                $result .= ':' . $entry->getColumn();
            }

            $result .= ')';
        }

        return $result;
    }

    /**
     * @param InvocationInterface $entry
     * @return non-empty-string
     */
    private function entryToString(InvocationInterface $entry): string
    {
        return match (true) {
            $entry instanceof MethodInvocationInterface => $this->methodEntryToString($entry),
            $entry instanceof FunctionInvocationInterface => $this->functionEntryToString($entry),
            default => throw new \InvalidArgumentException(
                'Can not print trace item "' . \get_class($entry) . '"'
            )
        };
    }

    /**
     * @param MethodInvocationInterface $method
     * @return non-empty-string
     */
    private function methodEntryToString(MethodInvocationInterface $method): string
    {
        return $method->getClassName() . $this->getMethodType($method) . $this->functionEntryToString($method);
    }

    /**
     * @param MethodInvocationInterface $method
     * @return non-empty-string
     */
    private function getMethodType(MethodInvocationInterface $method): string
    {
        try {
            $reflection = new \ReflectionMethod($method->getClassName(), $method->getName());

            return $reflection->isStatic() ? '::' : '->';
        } catch (\Throwable) {
            return '->';
        }
    }

    /**
     * @param FunctionInvocationInterface $fun
     * @return non-empty-string
     */
    private function functionEntryToString(FunctionInvocationInterface $fun): string
    {
        return $fun->getName() . '(' . $this->functionArgumentsToString($fun->getArguments()) . ')';
    }

    /**
     * @param array $args
     * @return string
     */
    private function functionArgumentsToString(array $args): string
    {
        return \implode(', ', \array_map($this->functionArgumentToString(...), $args));
    }

    /**
     * @param mixed $arg
     * @return non-empty-string
     */
    private function functionArgumentToString(mixed $arg): string
    {
        if ($this->info->prettyArgs) {
            return $this->functionPrettyArgumentToString($arg);
        }

        return match (true) {
            $arg === null => 'NULL',
            \is_string($arg) => "'" . $this->escape($this->limit($arg)) . "'",
            \is_bool($arg) => $arg ? 'true' : 'false',
            \is_float($arg), \is_int($arg) => (string)$arg,
            \is_object($arg) => 'Object(' . \explode("\0", \get_class($arg))[0] . ')',
            \is_array($arg) => 'Array',
            \gettype($arg) === 'resource (closed)',
            \is_resource($arg) => 'Resource id #' . \get_resource_id($arg),
            default => \get_debug_type($arg),
        };
    }

    /**
     * @param int $code
     * @return string
     */
    private function decToHex(int $code): string
    {
        $hex = \dechex($code);
        $hex = \str_pad($hex, 2, '0', \STR_PAD_LEFT);

        return \strtoupper($hex);
    }

    /**
     * @param string $value
     * @return string
     */
    private function escape(string $value): string
    {
        $result = '';

        foreach (\str_split($value) as $char) {
            $code = \ord($char);

            if ($code < 32 || $code > 126) {
                $char = '\\x' . $this->decToHex($code);
            }

            $result .= $char;
        }

        return $result;
    }

    /**
     * @param string $value
     * @return string
     */
    private function limit(string $value): string
    {
        return \strlen($value) > $this->info->stringLength
            ? \substr($value, 0, $this->info->stringLength) . '...'
            : $value
        ;
    }

    /**
     * @param mixed $arg
     * @return string
     */
    private function functionPrettyArgumentToString(mixed $arg): string
    {
        return match (true) {
            $arg === null => 'null',
            \is_string($arg) => 'string(' . \strlen($arg) . ') "' . $this->escape($this->limit($arg)) . '"',
            \is_bool($arg) => 'bool(' . ($arg ? 'true' : 'false') . ')',
            \is_float($arg) => 'float(' . $arg . ')',
            \is_int($arg) => 'int(' . $arg . ')',
            \is_object($arg) => 'object#' . \spl_object_id($arg) . '(' . \explode("\0", \get_class($arg))[0] . ')',
            \is_array($arg) => 'array(' . \count($arg) . ')',
            \gettype($arg) === 'resource (closed)' => 'resource#' . \get_resource_id($arg) . '(closed)',
            \is_resource($arg) => 'resource#' . \get_resource_id($arg) . '(' . \get_resource_type($arg) . ')',
            default => \get_debug_type($arg),
        };
    }
}
