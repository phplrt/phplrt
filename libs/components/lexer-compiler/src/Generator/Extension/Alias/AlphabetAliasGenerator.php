<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator\Extension\Alias;

final class AlphabetAliasGenerator implements AliasGeneratorInterface
{
    /**
     * @var non-empty-string
     */
    public const string ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';

    public function __construct(
        /**
         * @var non-empty-string
         */
        private readonly string $alphabet = self::ALPHABET,
    ) {}

    public function getAliasById(int $id): string
    {
        $base = \strlen($alphabet = $this->alphabet);

        ++$id;
        $result = '';

        while ($id > 0) {
            $result = $alphabet[--$id % $base] . $result;

            $id = \intdiv($id, $base);
        }

        return $result;
    }
}
