<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * Class GrammarException
 */
class GrammarException extends \LogicException
{
    /**
     * @var string
     */
    private const ERROR_HEADER = '%s: %s in %s:%d';

    /**
     * @var array
     */
    public $trace = [];

    /**
     * @return string
     */
    public function __toString(): string
    {
        $result = [
            \vsprintf(self::ERROR_HEADER, [
                static::class,
                $this->getMessage(),
                $this->getFile(),
                $this->getLine(),
            ]),
        ];

        if (\count($this->trace)) {
            $result[] = 'Grammar Stack Trace:';
            $result[] = $this->getInternalTraceAsString();
        }

        $result[] =  'Stack Trace:';
        $result[] = $this->getTraceAsString();

        return \implode("\n", $result);
    }

    /**
     * @return string
     */
    private function getInternalTraceAsString(): string
    {
        return \implode("\n", $this->getInternalTrace());
    }

    /**
     * @return array
     */
    private function getInternalTrace(): array
    {
        $format = static function (array $info, int $key) {
            return '#' . $key . ' ' . $info['file'] . '(' . $info['line'] . '): ' . $info['info'];
        };

        $trace = \array_reverse($this->trace);

        return \array_map($format, $trace, \array_keys($trace));
    }
}
