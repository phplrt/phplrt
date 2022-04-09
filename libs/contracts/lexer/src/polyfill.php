<?php

namespace Phplrt\Contracts\Lexer {

    if (!interface_exists(BufferInterface::class, false)) {
        class_alias(\Phplrt\Buffer\BufferInterface::class, BufferInterface::class);
    }

}
