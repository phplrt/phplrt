<?php

use Phplrt\Trace\Factory;

require __DIR__ . '/vendor/autoload.php';

function test(int $a, int &$b)
{
    echo (new Exception()) . "\n------------\n";
    return Factory::getInstance()->create();
}

$arg = 42;
$trace = test(42, $arg);

echo $trace;
