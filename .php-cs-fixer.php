<?php

$files = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/libs',
    ])
    ->exclude([
        __DIR__ . '/libs/compiler/src/Grammar',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        'strict_param' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
    ])
    ->setCacheFile(__DIR__ . '/vendor/.php-cs-fixer.cache')
    ->setFinder($files)
;
