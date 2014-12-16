<?php

/*
 * This file is part of Psy Shell
 *
 * (c) 2012-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Psy\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use JakubOnderka\PhpConsoleColor\ConsoleColor;
use JakubOnderka\PhpConsoleHighlighter\Highlighter;

/**
 * Show the context of where you opened the debugger.
 */
class WhereamiCommand extends Command
{

    public function __construct()
    {
        $this->backtrace = debug_backtrace();
        return parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('whereami')
            ->setDefinition(array(
                new InputOption('num', 'n', InputOption::VALUE_OPTIONAL, 'Number of lines before and after.', '5')
            ))
            ->setDescription('Show where you are in the code.')
            ->setHelp(
                <<<HELP
Show where you are in the code.

Optionally, include how many lines before and after you want to display.

e.g.
<return>> whereami </return>
<return>> whereami -n10</return>
HELP
            );
    }

    /**
     * Obtains the correct trace in the full backtrace.
     *
     * @return array
     */
    protected function trace()
    {
        foreach ($this->backtrace as $i => $backtrace) {
            if (!isset($backtrace['class'], $backtrace['function'])) {
                continue;
            }
            $correctClass = $backtrace['class'] === 'Psy\Shell';
            $correctFunction = $backtrace['function'] === 'debug';
            if ($correctClass && $correctFunction) {
                return $backtrace;
            }
        }
        return $backtrace[count($backtrace) - 1];
    }

    /**
     * Determine the file and line based on the specific backtrace.
     *
     * @return array
     */
    protected function fileInfo()
    {
        $backtrace = $this->trace();
        if (preg_match('/eval\(/', $backtrace['file'])) {
            preg_match_all('/([^\(]+)\((\d+)/', $backtrace['file'], $matches);
            $file = $matches[1][0];
            $line = (int) $matches[2][0];
        } else {
            $file = $backtrace['file'];
            $line = $backtrace['line'];
        }
        return compact('file', 'line');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        extract($this->fileInfo());
        $num = $input->getOption('num');
        $colors = new ConsoleColor();
        $colors->addTheme('line_number', array('blue'));
        $highlighter = new Highlighter($colors);
        $contents = file_get_contents($file);
        $output->page($highlighter->getCodeSnippet($contents, $line, $num, $num));
    }
}
