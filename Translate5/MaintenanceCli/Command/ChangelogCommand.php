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
use Zend_Exception;

class ChangelogCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'changelog';

    private array $headlines = [];

    protected function configure(): void
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Show the last changelog entries.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Tool to list the latest changelog entries.');

        $this->addOption(
            'important',
            'i',
            InputOption::VALUE_NONE,
            'Show the important release notes only.'
        );

        $this->addOption(
            'summary',
            's',
            InputOption::VALUE_NONE,
            'Show only a summary'
        );

        $this->addOption(
            'list',
            'l',
            InputOption::VALUE_NONE,
            'List only the available versions.'
        );

        $this->addOption(
            'exact',
            'e',
            InputOption::VALUE_NONE,
            'Shows only exactly the given version instead of from that version to the latest version.'
        );

        $this->addArgument(
            'version',
            InputArgument::OPTIONAL,
            'The version number from which or exactly for which the changelog is shown. Defaults to the latest one.'
        );
    }

    /**
     * @throws Zend_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $this->writeTitle('Translate5 latest change log:');

        if ($input->getOption('list')) {
            $this->io->writeln($this->readVersions());

            return self::SUCCESS;
        }

        $summary = $input->getOption('summary');
        $importantOnly = (bool) $input->getOption('important');
        $fromVersion = $this->input->getArgument('version');
        $isExactMatch = (bool) $input->getOption('exact');

        if ($isExactMatch && $fromVersion === null) {
            $this->io->error('When using -e|--exact a version must be given as argument to the command');

            return self::FAILURE;
        }

        if ($importantOnly) {
            $filter = ['Important Notes:']; //Bugfixes / Changed / Added
        } else {
            $filter = [];
        }

        $chunks = $this->readContentAsChunks($isExactMatch, $fromVersion);

        $chunk = array_shift($chunks);
        $isImportant = false;
        $currentType = null;
        $this->headlines = [];
        while (! is_null($chunk)) {
            $chunk = array_shift($chunks);
            if (is_null($chunk)) {
                return self::SUCCESS;
            }
            switch ($chunk) {
                case '##':
                    $isImportant = false;
                    $this->addHeadline('release', array_shift($chunks));

                    continue 2;
                case '###':
                    $head = $currentType = array_shift($chunks);
                    $isImportant = $head === 'Important Notes:';
                    $this->addHeadline('types', $head);

                    continue 2;
                case '####':
                    $head = array_shift($chunks);
                    if ($isImportant) {
                        $this->addHeadline('important', $head);
                    } else {
                        $this->addHeadline('default', $head);
                    }

                    continue 2;
                default:
                    break;
            }

            if ($currentType !== null && ! empty($filter) && ! in_array($currentType, $filter)) {
                continue;
            }

            $this->printHeadlines();
            $this->headlines = [];

            $this->printContent($chunk, $summary);
        }

        return self::SUCCESS;
    }

    private function addHeadline(string $type, string $head): void
    {
        $this->headlines[] = [
            'type' => $type,
            'text' => $head,
        ];
    }

    private function printHeadlines(): void
    {
        foreach ($this->headlines as $line) {
            switch ($line['type']) {
                case 'release':
                    $this->io->title($line['text']);

                    break;

                case 'types':
                    $this->io->section($line['text']);

                    break;

                case 'important':
                    $this->io->warning($line['text']);

                    break;

                default:
                    $this->io->text($line['text']);

                    break;
            }
        }
    }

    private function printContent(string $chunk, mixed $summary): void
    {
        $chunk = trim($chunk);
        if (strlen($chunk) > 0) {
            $matches = null;
            if ($summary && preg_match_all('#\*\*\[([^]]+)]\(([^)]+)\):(.+)\*\* <br>#', $chunk, $matches)) {
                foreach ($matches[1] as $idx => $key) {
                    //$url = $matches[2][$idx];
                    $subject = $matches[3][$idx];
                    $this->io->text('<info>' . $key . '</info> <options=bold>' . $subject . '</>');
                }
            } else {
                $chunk = preg_replace(
                    '#\*\*\[([^]]+)]\(([^)]+)\):(.+)\*\* <br>#',
                    "<info>$1</info> <options=bold>$3</> \n <fg=gray>$2</>",
                    $chunk
                );
                $this->io->text($chunk);
            }
            //<fg=yellow;options=bold>not optimal</>
        }
    }

    private function readVersions(): array
    {
        $content = file_get_contents(APPLICATION_ROOT . '/docs/CHANGELOG.md');
        $firstPos = mb_strpos($content, "\n## [");
        $content = substr($content, $firstPos);

        return array_filter(preg_split('/^(## \[.*$)/m', $content, flags: PREG_SPLIT_DELIM_CAPTURE), function ($item) {
            return str_starts_with($item, '## [');
        });
    }

    private function readContentAsChunks(bool $isExactMatch, ?string $fromVersion): array
    {
        $content = file_get_contents(APPLICATION_ROOT . '/docs/CHANGELOG.md');

        if ($isExactMatch) {
            $firstPos = mb_strpos($content, "\n## [" . $fromVersion); //exact match add version here: "\n## [7.20.5"
        } else {
            $firstPos = mb_strpos($content, "\n## [");
        }

        if ($fromVersion === null) {
            $toPos = $firstPos + 5;
        } else {
            if ($isExactMatch) {
                $toPos = mb_strpos($content, "\n## [", $firstPos + 5);
            } else {
                $toPos = mb_strpos($content, "\n## [" . $fromVersion, $firstPos + 5);
                $toPos = mb_strpos($content, "\n## [", $toPos + 5);
            }
        }
        $endPos = mb_strpos($content, "\n## [", $toPos);
        $content = substr($content, $firstPos, $endPos - $firstPos);

        return preg_split('/^(##+)\s*(.*)$/m', $content, flags: PREG_SPLIT_DELIM_CAPTURE);
    }
}
