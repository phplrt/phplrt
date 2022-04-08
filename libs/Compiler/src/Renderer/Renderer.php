<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Renderer;

abstract class Renderer implements RendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function fromString($data, int $depth = 0, bool $multiline = true): string
    {
        $prefix = $this->prefix($depth);

        if (\is_array($data)) {
            $result = [];

            foreach ($data as $key => $value) {
                $result[] = $this->renderKeyValue($key, $value, $depth + 1);
            }

            if ($multiline) {
                return "[\n" . \implode(",\n", $result) . "\n${prefix}]";
            }

            return '[' . \implode(', ', $result) . "${prefix}]";
        }

        return (string)$data;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param int $depth
     * @return string
     */
    private function renderKeyValue($key, $value, int $depth = 0): string
    {
        return \sprintf('%s => %s', $this->prefix($depth) . $this->fromPhp($key), $value);
    }

    /**
     * {@inheritDoc}
     */
    public function prefix(int $depth): string
    {
        return \str_repeat(' ', $depth * 4);
    }
}
