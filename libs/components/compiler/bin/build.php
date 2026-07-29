<?php

declare(strict_types=1);

use Phplrt\Compiler\Command\GrammarCompileCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__ . '/bootstrap.php';

$app = new Application(PHPLRT_NAME, PHPLRT_VERSION);
$app->addCommand(new GrammarCompileCommand());

const COMMANDS = [
    [
        'compile',
        'grammar' => __DIR__ . '/../resources/pp2.pp3',
        'output' => __DIR__ . '/../src/Syntax/PP2/PP2Parser.php',
        '--namespace' => 'Phplrt\Compiler\Syntax\PP2',
        '--class' => 'PP2Parser',
    ],
    [
        'compile',
        'grammar' => __DIR__ . '/../resources/pp3.pp3',
        'output' => __DIR__ . '/../src/Syntax/PP3/PP3Parser.php',
        '--namespace' => 'Phplrt\Compiler\Syntax\PP3',
        '--class' => 'PP3Parser',
    ],
];

foreach (COMMANDS as $command) {
    $app->doRun(new ArrayInput($command), new ConsoleOutput());
}
