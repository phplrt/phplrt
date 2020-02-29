<?php


use Phplrt\Compiler\Compiler;

require __DIR__ . '/vendor/autoload.php';

$c = new Compiler();
$c->load(/** @lang PHPT */'
    %token d \d+
    %token p \+
    %skip ws \s+ 
    
    sum -> {
        dump($offset);
        
        foreach ($children as $child) {
            dump($child);
        }

        return $children;
    } = <d> sfx()+;
    sfx -> { dump($children); } = ::p:: <d>;
');

$generator = $c->build();
$generator->withClassUsage(\Phplrt\Parser\Parser::class);

echo $generator;
