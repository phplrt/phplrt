<?php

use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Lexer\Lexer;
use Phplrt\Parser\Context;
use Phplrt\Parser\Parser;
use Phplrt\Position\Content;
use Phplrt\Source\File;

require __DIR__ . '/vendor/autoload.php';


$c = new Content(File::fromSources('
2.
3.
4.
5.
6.
'));

foreach ($c->lines(4, 0, 2) as $line) {
    dump($line);
}

