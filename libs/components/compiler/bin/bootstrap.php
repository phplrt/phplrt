<?php

declare(strict_types=1);

if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
    trigger_error('The console should be invoked via the CLI '
        . 'version of PHP, not the ' . PHP_SAPI . ' SAPI.', E_USER_WARNING);
}

$directory = __DIR__;
while (dirname($directory) !== $directory) {
    foreach ([$directory . '/autoload.php', $directory . '/vendor/autoload.php'] as $pathname) {
        if (is_file($pathname)) {
            require $pathname;
            break;
        }
    }

    $directory = dirname($directory);
}
