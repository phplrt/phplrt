<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Node\Annotation;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Exception\MessagePlaceholder;

/**
 * Occurs when a rule or a statement is said something the compiler knows
 * nothing about, or is said it in a way it cannot be said.
 */
final class UnsupportedAnnotationException extends UnsupportedSyntaxException
{
    public static function becauseAnnotationIsNotSupported(
        ReadableInterface $source,
        Annotation $annotation,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('Unrecognized annotation "@%s"', $annotation->name);

        return self::create($source, $annotation, $message, $previous);
    }

    /**
     * @param int<0, max> $expected
     */
    public static function becauseAnnotationExpectsValues(
        ReadableInterface $source,
        Annotation $annotation,
        int $expected,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf(
            'The "@%s" annotation expects %d value(s), %d given',
            $annotation->name,
            $expected,
            \count($annotation->arguments),
        );

        return self::create($source, $annotation, $message, $previous);
    }

    public static function becauseAnnotationIsWrittenTwice(
        ReadableInterface $source,
        Annotation $annotation,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('The "@%s" annotation is said about the same rule twice', $annotation->name);

        return self::create($source, $annotation, $message, $previous);
    }

    public static function becauseAnnotationExpectsNonEmptyValue(
        ReadableInterface $source,
        Annotation $annotation,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('The "@%s" annotation expects a value that is not empty', $annotation->name);

        return self::create($source, $annotation, $message, $previous);
    }

    /**
     * @param non-empty-string $placeholder
     */
    public static function becausePlaceholderIsNotSupported(
        ReadableInterface $source,
        Annotation $annotation,
        string $placeholder,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf(
            'Unrecognized placeholder "{%s}" of the "@%s" annotation, one of %s expected',
            $placeholder,
            $annotation->name,
            \implode(', ', \array_map(
                static fn(string $name): string => \sprintf('"{%s}"', $name),
                MessagePlaceholder::names(),
            )),
        );

        return self::create($source, $annotation, $message, $previous);
    }

    private static function create(
        ReadableInterface $source,
        Annotation $annotation,
        string $message,
        ?\Throwable $previous,
    ): self {
        return new self(
            source: $source,
            offset: $annotation->offset,
            message: $message,
            length: $annotation->length,
            previous: $previous,
        );
    }
}
