<?php

declare(strict_types=1);

namespace Phplrt\Source\Hash;

final readonly class XXHash3Hasher implements HasherInterface
{
    private const string INTERNAL_HASH_ALGO_NAME = 'xxh3';

    public function content(string $value): string
    {
        return \hash(self::INTERNAL_HASH_ALGO_NAME, $value);
    }

    public function file(string $pathname): string
    {
        $hash = \hash_file(self::INTERNAL_HASH_ALGO_NAME, $pathname);

        if ($hash === false) {
            throw new \RuntimeException(\sprintf('Unable to get hash of a file "%s"', $pathname));
        }

        return $hash;
    }
}
