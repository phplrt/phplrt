<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Renderer;

abstract class Renderer implements RendererInterface
{
    public function fromString($data, int $depth = 0, bool $multiline = true): string
    {
        $prefix = $this->prefix($depth);

        if (\is_array($data)) {
            $result = [];

            /** @var mixed $value */
            foreach ($data as $key => $value) {
                $result[] = $this->renderKeyValue($key, $value, $depth + 1);
            }

            if ($multiline) {
                return \sprintf("[\n%s\n%s]", \implode(",\n", $result), $prefix);
            }

            return \sprintf("[%s%s]", \implode(', ', $result), $prefix);
        }

        return (string) $data;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param int<0, max> $depth
     * @return non-empty-string
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    private function renderKeyValue($key, $value, int $depth = 0): string
    {
        return \sprintf('%s => %s', $this->prefix($depth) . $this->fromPhp($key), (string) $value);
    }

    public function prefix(int $depth): string
    {
        return \str_repeat(' ', $depth * 4);
    }
}
