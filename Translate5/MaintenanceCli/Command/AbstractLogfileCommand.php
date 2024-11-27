<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

namespace Translate5\MaintenanceCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractLogfileCommand extends Translate5AbstractCommand
{
    public const LIMIT = 25;

    /**
     * The keywords  the lines should match
     */
    protected array $keywords = [];

    private int $pointer;

    /**
     * Must be defined in extending classes
     */
    abstract protected function getLogFilePath(): string;

    abstract protected function getCommandDescription(): string;

    protected function configure()
    {
        $desription = $this->getCommandDescription();

        $this
            ->setDescription($desription)
            ->setHelp($desription);

        $this->addArgument(
            'filter',
            InputArgument::OPTIONAL,
            'Provide keywords to filter output. All lines containing the keywords will be shown.'
        );

        $this->addOption(
            'follow',
            'f',
            InputOption::VALUE_NONE,
            'Continuously print new entries as they are appended to the log.'
        );

        $this->addOption(
            'last',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Shows the last X lines (default is 25).',
            false
        );
    }

    /**
     * @throws \Zend_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $filter = $this->input->getArgument('filter');
        if (is_string($filter) && strlen($filter) > 0) {
            foreach (explode(' ', $filter) as $item) {
                if (trim($item) !== '') {
                    $this->keywords[] = trim($item);
                }
            }
        }

        $limit = $input->getOption('last');
        if (! empty($limit)) {
            $limit = (int) trim($limit, '-=');
        } else {
            $limit = static::LIMIT;
        }

        foreach ($this->getLastLines($limit) as $line) {
            $this->io->writeln($line);
        }

        if ($input->getOption('follow')) {
            while (true) { // @phpstan-ignore-line
                sleep(2);

                foreach ($this->getLastLines(-1, $this->pointer) as $line) {
                    $this->io->writeln($line);
                }
            }
        }

        return static::SUCCESS;
    }

    /**
     * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
     * @author Torleif Berger, Lorenzo Stanco
     * @link http://stackoverflow.com/a/15025877/995958
     * @license http://creativecommons.org/licenses/by/3.0/
     */
    protected function getLastLines(int $numLines, int $min = 0): array
    {
        $filepath = $this->getLogFilePath();
        // Open file
        $f = @fopen($filepath, 'rb');
        if ($f === false) {
            $this->io->error('Log file "' . $filepath . '" not found or not readable.');
            exit(0);
        }

        if (count($this->keywords) === 0) {
            // Sets buffer size, according to the number of lines to retrieve.
            // This gives a performance boost when reading a few lines from the file.
            $buffer = ($numLines < 2 ? 64 : ($numLines < 10 ? 512 : 4096));
        } else {
            // always read a bigger chunk, we don't know what to expect
            $buffer = 4096;
        }

        // Jump to last character
        fseek($f, -1, SEEK_END);

        $this->pointer = ftell($f) + 1;

        // Start reading
        $output = '';
        $relevantLines = [];

        // While we would like more
        while (ftell($f) > $min && ($numLines === -1 || count($relevantLines) < $numLines)) {
            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);

            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);

            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;

            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

            // create chunks
            $lines = explode("\n", $output);
            $count = count($lines);
            $batch = [];
            for ($i = 1; $i < $count; $i++) {
                if ($this->isRelevantLine($lines[$i])) {
                    $batch[] = $lines[$i];
                }
            }
            // if we had matches, we prepend the batch to the relevant lines - we are reading from the back
            if (count($batch) > 0) {
                array_unshift($relevantLines, ...$batch);
            }
            // first line for the next run - we search only lines that we know are complete
            $output = $lines[0];
        }

        // Close file and return
        fclose($f);

        return $relevantLines;
    }

    private function isRelevantLine(string $line): bool
    {
        if (count($this->keywords) === 0) {
            return true;
        }
        foreach ($this->keywords as $keyword) {
            if (str_contains($line, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
