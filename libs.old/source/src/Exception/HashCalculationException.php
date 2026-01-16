<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\SourceExceptionInterface;

class HashCalculationException extends \LogicException implements SourceExceptionInterface
{
    /**
     * @final
     */
    public const CODE_INVALID_HASH_ALGO = 0x01;

    final public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function fromInvalidHashAlgo(string $algo, ?\Throwable $prev = null): self
    {
        $message = 'Cannot get the source hash because the algorithm "%s" '
            . 'is not supported by the PHP environment or is incorrect';

        return new static(\sprintf($message, $algo), self::CODE_INVALID_HASH_ALGO, $prev);
    }
}
