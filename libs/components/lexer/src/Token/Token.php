<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Printer\PrettyTokenPrinter;

/**
 * @readonly
 */
class Token implements TokenInterface
{
    public function __construct(
        public int $id,
        /**
         * @var non-empty-string|null
         */
        public ?string $name,
        public ChannelInterface $channel,
        public ReadableInterface $source,
        public string $value,
        /**
         * @var int<0, max>
         */
        public int $offset = self::MIN_OFFSET,
    ) {}

    public function __toString(): string
    {
        /** @var PrettyTokenPrinter $printer */
        static $printer = new PrettyTokenPrinter();

        return $printer->print($this);
    }
}
