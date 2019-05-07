<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Console;

use Phplrt\Io\File;
use Phplrt\Compiler\Compiler;
use Phplrt\Exception\ExternalException;
use Phplrt\Io\Exception\NotReadableException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class CompileCommand
 */
class CompileCommand extends Command
{
    /**
     * @param InputInterface $in
     * @param OutputInterface $out
     * @throws ExternalException
     * @throws NotReadableException
     * @throws \Throwable
     */
    public function execute(InputInterface $in, OutputInterface $out): void
    {
        $compiler = Compiler::load(File::fromPathname($in->getArgument('grammar')));

        if ($in->getOption('namespace')) {
            $compiler->setNamespace($in->getOption('namespace'));
        }

        if ($in->getOption('class')) {
            $compiler->setClassName($in->getOption('class'));
        }

        $compiler->saveTo($in->getOption('dir') ?: \getcwd());
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('compile');
        $this->setDescription('Builds a parser from pp2 grammar file.');

        $this->addArgument('grammar', InputArgument::REQUIRED, 'Path to pp2 grammar file');

        $this->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'Output parser directory');

        $this->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Output parser class name');

        $this->addOption('namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Output parser namespace');
    }
}
