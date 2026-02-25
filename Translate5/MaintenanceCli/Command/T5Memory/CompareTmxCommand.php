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

namespace Translate5\MaintenanceCli\Command\T5Memory;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;
use XMLReader;

class CompareTmxCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 't5memory:tmx:compare';

    protected function configure()
    {
        $this->addArgument(
            'file1',
            InputOption::VALUE_REQUIRED,
            'The TMX file to filter'
        );

        $this->addArgument(
            'file2',
            InputOption::VALUE_REQUIRED,
            'The TMX file to filter'
        );

        $this->addOption(
            'omit-author',
            '-a',
            InputOption::VALUE_NEGATABLE,
            'Do not keep segments with different author',
            false
        );

        $this->addOption(
            'omit-context',
            '-c',
            InputOption::VALUE_NEGATABLE,
            'Do not keep segments with different context',
            false
        );

        $this->addOption(
            'omit-document',
            '-d',
            InputOption::VALUE_NEGATABLE,
            'Do not keep segments with different document',
            false
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // INIT APPLICATION
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        // INIT APPLICATION

        $omitAuthor = (bool) $input->getOption('omit-author');
        $omitContext = (bool) $input->getOption('omit-context');
        $omitDocument = (bool) $input->getOption('omit-document');

        $file1 = basename($input->getArgument('file1'));
        $file2 = basename($input->getArgument('file2'));
        $segments1 = $this->calcTmx($input->getArgument('file1'), $omitAuthor, $omitContext, $omitDocument);
        $segments2 = $this->calcTmx($input->getArgument('file2'), $omitAuthor, $omitContext, $omitDocument);

        $this->io->writeln('');

        $lostCount = 0;
        $diffCount = 0;
        foreach ($segments1 as $hash => $data) {
            if (! isset($segments2[$hash])) {
                $lostCount++;
                $this->io->writeln('<error>Segment not in file 2:</error>' . PHP_EOL . $data['source'] . PHP_EOL . $data['parts'] . PHP_EOL);

                continue;
            }

            if ($data['timestamp'] !== $segments2[$hash]['timestamp'] || $data['target'] !== $segments2[$hash]['target']) {
                $diffCount++;
                $this->io->section('Segment differs for segment: ' . $data['source']);
            }

            if ($data['timestamp'] !== $segments2[$hash]['timestamp']) {
                $this->io->writeln('Timestamp differs: ' . ' File1: ' . date('c', $data['timestamp']) . ' File2: ' . date('c', $segments2[$hash]['timestamp']));
            }

            if ($data['target'] !== $segments2[$hash]['target']) {
                $this->io->error('Target differs');

                $this->io->writeln($file1 . ':');
                $this->io->writeln($data['target']);

                $this->io->writeln($file2 . ':');
                $this->io->writeln($segments2[$hash]['target']);
            }
        }

        if ($lostCount > 0) {
            $this->io->error('Lost ' . $lostCount . ' segments');
        }

        if ($diffCount > 0) {
            $this->io->info('Found ' . $diffCount . ' differing segments.');
        }

        return 0;
    }

    private function calcTmx(
        string $file,
        bool $omitAuthor,
        bool $omitContext,
        bool $omitDocument,
    ): array {
        $sourceLang = null;
        $reader = new XMLReader();
        if (! $reader->open($file)) {
            $this->output->writeln('<error>Could not open file ' . $file . '</error>');

            throw new \RuntimeException('Could not open file ' . $file);
        }

        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        $segments = [];

        $tuCount = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'header') {
                continue;
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                $tuCount++;
                $author = '';
                $date = 0;
                $docname = '';
                $context = '';
                $sourceSegment = '';
                $targetSegment = '';

                $tu = $reader->readOuterXML();

                if ('' === $tu) {
                    $this->io->error('Could not read TU in file: ' . $file);
                    $this->io->info((string) $tuCount);

                    continue;
                }

                $xml = XMLReader::XML($tu);

                if (is_bool($xml)) {
                    $this->io->error('Could not parse TU: ' . $tu);

                    continue;
                }

                while ($xml->read()) {
                    if ($xml->nodeType !== XMLReader::ELEMENT) {
                        continue;
                    }

                    if ($xml->name === 'prop') {
                        // @phpstan-ignore-next-line
                        if ($xml->getAttribute('type') === 'tmgr:docname') {
                            $docname = $xml->readInnerXml();
                        }

                        // @phpstan-ignore-next-line
                        if ($xml->getAttribute('type') === 'tmgr:context') {
                            $context = $xml->readInnerXml();
                        }

                        continue;
                    }

                    if ($xml->name !== 'tuv') {
                        continue;
                    }

                    $lang = strtolower($xml->getAttribute('xml:lang'));

                    if (null === $sourceLang) {
                        $sourceLang = $lang;
                    }

                    $segment = str_replace(['<seg>', '</seg>'], '', trim($xml->readInnerXml()));

                    if ($this->isSourceTuv($lang, $sourceLang)) {
                        $sourceSegment = $segment;
                    } else {
                        $targetSegment = $segment;
                    }
                }

                if ($reader->hasAttributes) {
                    while ($reader->moveToNextAttribute()) {
                        // @phpstan-ignore-next-line
                        if ($reader->name === 'creationdate') {
                            $date = strtotime($reader->value);
                        }

                        // @phpstan-ignore-next-line
                        if ($reader->name === 'creationid') {
                            $author = $reader->value;
                        }
                    }
                }

                $hashParts = [
                    $sourceSegment,
                ];

                if (! $omitAuthor) {
                    $hashParts[] = strtolower($author);
                }
                if (! $omitDocument) {
                    $hashParts[] = $docname ?: 'none';
                }
                if (! $omitContext) {
                    $hashParts[] = $context ?: '-';
                }

                $hash = md5(implode('|', $hashParts));

                // @phpstan-ignore-next-line
                if (isset($segments[$hash]) && $segments[$hash]['timestamp'] >= $date) {
                    continue;
                }

                $segments[$hash] = [
                    'timestamp' => $date,
                    'source' => $sourceSegment,
                    'target' => $targetSegment,
                    'parts' => implode('|', $hashParts),
                ];
            }
        }

        $this->io->section('File: ' . $file);
        $this->io->writeln('Found ' . $tuCount . ' TUs in total.');
        $this->io->writeln('Keeping ' . count($segments) . ' TUs after filtering.');

        return $segments;
    }

    private function isSourceTuv(string $tuvLang, string $sourceLang): bool
    {
        return strtolower($sourceLang) === $tuvLang;
    }
}
