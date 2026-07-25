<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Internal;

/**
 * Splits the source code lines into the parts fitting into the available width.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class LineWrapper
{
    public function __construct(
        private Text $text = new Text(),
    ) {}

    /**
     * @param int<1, max> $available the number of columns available for a part
     * @param int<0, max> $marker the number of columns occupied by the mark of
     *        a continuation
     * @return non-empty-list<LinePart>
     */
    public function split(string $value, int $available, int $marker): array
    {
        $length = $this->text->calculateLength($value);

        // The marks are omitted in case they leave no room for the line itself
        if ($available <= 2 * $marker) {
            $marker = 0;
        }

        $result = [];
        $offset = 0;

        while (true) {
            $indent = $result === [] ? 0 : $marker;
            $rest = $length - $offset;

            if ($rest <= $available - $indent) {
                $result[] = new LinePart(
                    $this->text->slice($value, $offset, \max(0, $rest)),
                    $offset,
                    $length,
                    $indent,
                    false,
                );

                return $result;
            }

            $take = \max(1, $available - $indent - $marker);

            $result[] = new LinePart(
                $this->text->slice($value, $offset, $take),
                $offset,
                $offset + $take,
                $indent,
                $marker > 0,
            );

            $offset += $take;
        }
    }
}
