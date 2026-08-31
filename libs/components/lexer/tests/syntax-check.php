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


// lookup source files
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../src', FilesystemIterator::SKIP_DOTS),
);

$count = $code = 0;

/**
 * analyze source code syntax
 *
 * @var SplFileInfo $file
 */
foreach ($files as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $pathname = \str_replace('\\', '/', $file->getPathname());

    if (!\str_contains($pathname, '/src/') || !\str_ends_with($pathname, '.php')) {
        continue;
    }

    ++$count;

    try {
        token_get_all(file_get_contents($pathname), TOKEN_PARSE);
    } catch (\Throwable $e) {
        $code = 1;
        echo \sprintf("> %s in %s on line %d\n", $e->getMessage(), $pathname, $e->getLine());
    }
}

if ($code === 0) {
    echo sprintf("Analyzed %d files\n", $count);
} else {
    exit($code);
}
