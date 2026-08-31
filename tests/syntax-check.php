<?php

declare(strict_types=1);

// load composer's autoload
$directory = __DIR__;
while (dirname($directory) !== $directory) {
    foreach ([$directory . '/autoload.php', $directory . '/vendor/autoload.php'] as $pathname) {
        if (is_file($pathname)) {
            require_once $pathname;
            break;
        }
    }

    $directory = dirname($directory);
}

$code = 0;

// analyze components
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/components/compiler/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/components/exception/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/components/lexer/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/components/lexer-builder/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/components/parser/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/components/parser-builder/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/components/position/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/components/source/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }

// analyze contracts
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/contracts/lexer/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/contracts/parser/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/contracts/position/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/contracts/position-factory/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/contracts/source/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }
passthru(PHP_BINARY . ' ' . __DIR__ . '/../libs/contracts/source-factory/tests/syntax-check.php', $exit);
if ($exit !== 0) { $code = $exit; }

if ($code !== 0) {
    exit($code);
}
