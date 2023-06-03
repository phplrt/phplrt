<?php

namespace Phplrt\Contracts\Lexer {
    if (
        !\interface_exists(BufferInterface::class, false)
        && \interface_exists(\Phplrt\Buffer\BufferInterface::class)
    ) {
        \class_alias(\Phplrt\Buffer\BufferInterface::class, BufferInterface::class);
    }
}
