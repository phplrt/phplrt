<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * An exception that occurs when there is no read access to the file,
 * such as "Access Denied".
 */
class NotReadableException extends NotAccessibleException
{
    /**
     * @final
     */
    public const CODE_INTERNAL = 0x01 + parent::CODE_LAST;

    /**
     * @final
     */
    public const CODE_OPENING_FILE = 0x02 + parent::CODE_LAST;

    /**
     * @final
     */
    public const CODE_INVALID_STREAM = 0x03 + parent::CODE_LAST;

    protected const CODE_LAST = self::CODE_INVALID_STREAM + parent::CODE_LAST;

    /**
     * @psalm-taint-sink file $filename
     * @param non-empty-string $filename
     *
     * @return static
     */
    public static function fromInternalFileError(string $filename, \Throwable $e): self
    {
        $message = 'An unrecognized error occurred while reading the file "%s": %s';
        $message = \sprintf($message, $filename, $e->getMessage());

        return new static($message, self::CODE_INTERNAL, $e);
    }

    /**
     * @return static
     */
    public static function fromInternalStreamError(\Throwable $e): self
    {
        $message = 'An unrecognized error occurred while reading the stream: %s';
        $message = \sprintf($message, $e->getMessage());

        return new static($message, self::CODE_INTERNAL, $e);
    }

    public static function createFromLastInternalError(): \ErrorException
    {
        $error = \error_get_last();

        return new \ErrorException(
            $error['message'] ?? 'An error occurred',
            0,
            $error['type'] ?? \E_ERROR,
            $error['file'] ?? __FILE__,
            $error['line'] ?? __LINE__,
        );
    }

    /**
     * @psalm-taint-sink file $filename
     * @param non-empty-string $filename
     *
     * @return static
     */
    public static function fromOpeningFile(string $filename, ?\Throwable $prev = null): self
    {
        $message = 'An error occurred while trying to open the file "%s" for reading';

        return new static(\sprintf($message, $filename), self::CODE_OPENING_FILE, $prev);
    }

    /**
     * @return static
     */
    public static function fromInvalidResource(mixed $stream): self
    {
        $message = 'The "%s" is not valid resource stream';
        $message = \sprintf($message, \str_replace("\0", '\0', \get_debug_type($stream)));

        return new static($message, self::CODE_INVALID_STREAM);
    }

    /**
     * @param resource $stream
     *
     * @return static
     */
    public static function fromInvalidStream(mixed $stream): self
    {
        assert(\is_resource($stream));

        $message = 'The resource of type "%s" is not valid resource stream';
        $message = \sprintf($message, \get_resource_type($stream));

        return new static($message, self::CODE_INVALID_STREAM);
    }
}
