<?php

$files = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/libs'])
    ->exclude([__DIR__ . '/libs/compiler/src/Grammar']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        // PHP 7.4 doesn't support this rule.
        'trailing_comma_in_multiline' => false,
    ])
    ->setCacheFile(__DIR__ . '/vendor/.php-cs-fixer.cache')
    ->setFinder($files);
