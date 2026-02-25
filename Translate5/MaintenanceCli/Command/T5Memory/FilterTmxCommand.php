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

class FilterTmxCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 't5memory:tmx:filter';

    protected function configure()
    {
        $this->addArgument(
            'file',
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

        $sourceLang = null;

        $time = microtime(true);

        $reader = new XMLReader();
        if (! $reader->open($input->getArgument('file'))) {
            $this->output->writeln('<error>Could not open file ' . $input->getArgument('file') . '</error>');

            return self::FAILURE;
        }

        $resultingFile = basename($input->getArgument('file'), '.tmx') . '.filtered.tmx';
        $filterFolder = APPLICATION_DATA . '/tmx-filter/' . bin2hex(random_bytes(8));

        if (! @mkdir($filterFolder, 0777, true) && ! is_dir($filterFolder)) {
            $this->io->error('Could not create temporary folder ' . $filterFolder);

            return self::FAILURE;
        }
        $path = $filterFolder . '/' . $resultingFile;

        file_put_contents(
            $path,
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL,
            FILE_APPEND
        );

        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        $segments = [];

        $tuCount = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'header') {
                file_put_contents(
                    $path,
                    '<tmx version="1.4">' . PHP_EOL . $reader->readOuterXML() . PHP_EOL . '<body>' . PHP_EOL,
                    FILE_APPEND
                );

                continue;
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                $tuCount++;
                $author = '';
                $date = 0;
                $docname = '';
                $context = '';
                $sourceSegment = '';

                $tu = $reader->readOuterXML();
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
                    $hashParts[] = $author;
                }
                if (! $omitDocument) {
                    $hashParts[] = $docname;
                }
                if (! $omitContext) {
                    $hashParts[] = $context;
                }

                $hash = md5(implode('|', $hashParts));

                // @phpstan-ignore-next-line
                if (isset($segments[$hash]) && $segments[$hash]['timestamp'] >= $date) {
                    continue;
                }

                $segments[$hash] = [
                    'timestamp' => $date,
                    'tu' => gzcompress($tu),
                ];
            }
        }

        $this->io->writeln('Found ' . $tuCount . ' TUs in total.');
        $this->io->writeln('Keeping ' . count($segments) . ' TUs after filtering.');

        foreach ($segments as ['timestamp' => $date, 'tu' => $tu]) {
            file_put_contents(
                $path,
                gzuncompress($tu) . PHP_EOL,
                FILE_APPEND
            );
        }

        file_put_contents(
            $path,
            '</body>' . PHP_EOL . '</tmx>',
            FILE_APPEND
        );

        $this->io->writeln('Result file: ' . $path);

        $this->io->info('Filtering took ' . round(microtime(true) - $time, 2) . ' seconds.');

        return 0;
    }

    private function isSourceTuv(string $tuvLang, string $sourceLang): bool
    {
        return strtolower($sourceLang) === $tuvLang;
    }
}
