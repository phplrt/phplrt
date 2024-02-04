<?php

namespace Phplrt\Lexer\Buffer {

    if (
        !\class_exists(Buffer::class, false)
        && \class_exists(\Phplrt\Buffer\Buffer::class)
    ) {
        /** @psalm-suppress UndefinedClass */
        \class_alias(\Phplrt\Buffer\Buffer::class, Buffer::class);
    }

    if (
        !\class_exists(ArrayBuffer::class, false)
        && \class_exists(\Phplrt\Buffer\ArrayBuffer::class)
    ) {
        /** @psalm-suppress UndefinedClass */
        \class_alias(\Phplrt\Buffer\ArrayBuffer::class, ArrayBuffer::class);
    }

    if (
        !\class_exists(ExtrusiveBuffer::class, false)
        && \class_exists(\Phplrt\Buffer\ExtrusiveBuffer::class, false)
    ) {
        /** @psalm-suppress UndefinedClass */
        \class_alias(\Phplrt\Buffer\ExtrusiveBuffer::class, ExtrusiveBuffer::class);
    }

    if (
        !\class_exists(LazyBuffer::class, false)
        && \class_exists(\Phplrt\Buffer\LazyBuffer::class)
    ) {
        /** @psalm-suppress UndefinedClass */
        \class_alias(\Phplrt\Buffer\LazyBuffer::class, LazyBuffer::class);
    }
}
