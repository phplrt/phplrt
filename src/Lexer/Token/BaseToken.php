<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class BaseToken
 */
abstract class BaseToken implements TokenInterface
{
    /**
     * @var int
     */
    protected const TO_STRING_VALUE_LENGTH = 30;

    /**
     * @var string
     */
    protected const TO_STRING_VALUE_WRAP = '"%s"';

    /**
     * @var string
     */
    protected const TO_STRING_SPECIAL_CHARS = [
        ["\0", "\n", "\t"],
        ['\0', '\n', '\t'],
    ];

    /**
     * @var int|null
     */
    private $bytes;

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'id'     => $this->getType(),
            'value'  => $this->getValue(),
            'offset' => $this->getOffset(),
            'length' => $this->getBytes(),
        ];
    }

    /**
     * @return int
     */
    public function getBytes(): int
    {
        return $this->bytes ?? $this->bytes = \strlen($this->getValue());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $value = $this->getEscapedValue();

        if (\mb_strlen($value) > static::TO_STRING_VALUE_LENGTH + 5) {
            $suffix = \sprintf(' (%s+)', \mb_strlen($value) - static::TO_STRING_VALUE_LENGTH);
            $prefix = \sprintf(static::TO_STRING_VALUE_WRAP, \mb_substr($value, 0, static::TO_STRING_VALUE_LENGTH) . '…');

            return $this->replaceSpecialChars($prefix . $suffix);
        }

        return $this->replaceSpecialChars(\sprintf(static::TO_STRING_VALUE_WRAP, $value));
    }

    /**
     * @param string $value
     * @return string
     */
    private function replaceSpecialChars(string $value): string
    {
        return \str_replace(static::TO_STRING_SPECIAL_CHARS[0], static::TO_STRING_SPECIAL_CHARS[1], $value);
    }

    /**
     * @return string
     */
    private function getEscapedValue(): string
    {
        $value = $this->getValue();
        $value = (string)(\preg_replace('/\h+/u', ' ', $value) ?? $value);

        return \addcslashes($value, '"');
    }
}
