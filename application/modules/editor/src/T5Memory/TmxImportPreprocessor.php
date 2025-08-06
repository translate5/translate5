<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory;

use editor_Models_Languages as Language;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\T5Memory\Contract\TmxImportProcessor;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Exception\UnableToCreateFileForTmxPreprocessingException;
use MittagQI\Translate5\T5Memory\TmxImportPreprocessor\ContentProtectionProcessor;
use MittagQI\Translate5\T5Memory\TmxImportPreprocessor\RemoveCompromisedSegmentsProcessor;
use MittagQI\Translate5\T5Memory\TmxImportPreprocessor\TranslationUnitResegmentProcessor;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger;
use XMLReader;
use XMLWriter;
use Zend_Registry;
use ZfExtended_Logger;

class TmxImportPreprocessor
{
    /**
     * @param iterable<TmxImportProcessor> $tmxImportProcessors
     */
    public function __construct(
        private readonly LanguageRepository $languageRepository,
        private readonly iterable $tmxImportProcessors,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LanguageRepository::create(),
            [
                RemoveCompromisedSegmentsProcessor::create(),
                ContentProtectionProcessor::create(),
                TranslationUnitResegmentProcessor::create(),
            ],
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.tmx-import-preprocessing'),
        );
    }

    public function process(
        string $filepath,
        int $sourceLangId,
        int $targetLangId,
        ImportOptions $importOptions,
    ): string {
        if (empty($this->tmxImportProcessors)) {
            return $filepath;
        }

        $sourceLang = $sourceLangId ? $this->languageRepository->find($sourceLangId) : null;
        $targetLang = $targetLangId ? $this->languageRepository->find($targetLangId) : null;

        if (! $sourceLang || ! $targetLang) {
            throw new \InvalidArgumentException(
                'Source and target language must be valid language IDs.'
            );
        }

        $processorChain = $this->getProcessorsChain($sourceLang, $targetLang, $importOptions);

        if (null === $processorChain) {
            return $filepath;
        }

        $processingDir = APPLICATION_PATH . '/../data/TmxImportPreprocessing/';
        $problematicDir = $processingDir . 'problematic/';

        if (! is_dir($processingDir)) {
            @mkdir($processingDir, recursive: true);
        }

        if (! is_dir($problematicDir)) {
            @mkdir($problematicDir, recursive: true);
        }

        $processedFilename = str_replace('.tmx', '', basename($filepath)) . '_processed.tmx';
        $problematicFilename = str_replace('.tmx', '', basename($filepath)) . '_problematic.tmx';

        $resultFilepath = $processingDir . $processedFilename;
        $problematicFilepath = $problematicDir . $problematicFilename;

        $writer = new XMLWriter();

        if (! $writer->openURI($resultFilepath)) {
            throw new UnableToCreateFileForTmxPreprocessingException($resultFilepath);
        }

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        $reader = new XMLReader();
        $reader->open($filepath);
        $writtenElements = 0;

        // suppress: namespace error : Namespace prefix t5 on n is not defined
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        $brokenTranslationUnitIndicator = BrokenTranslationUnitLogger::create($this->logger, $problematicFilepath);

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'header') {
                $writer->writeRaw($reader->readOuterXML());
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                $tus = $processorChain->process(
                    $reader->readOuterXML(),
                    $sourceLang,
                    $targetLang,
                    $importOptions,
                    $brokenTranslationUnitIndicator,
                );

                foreach ($tus as $transUnit) {
                    if (empty($transUnit)) {
                        continue;
                    }

                    $writer->writeRaw($transUnit);
                    $writtenElements++;
                }
            }

            if (! in_array($reader->name, ['tmx', 'body'], true)) {
                continue;
            }

            if ($reader->nodeType == XMLReader::ELEMENT) {
                $writer->startElement($reader->name);

                if ($reader->hasAttributes) {
                    while ($reader->moveToNextAttribute()) {
                        $writer->writeAttribute($reader->name, $reader->value);
                    }
                }

                if ($reader->isEmptyElement) {
                    $writer->endElement();
                }
            }
        }

        error_reporting($errorLevel);

        $reader->close();

        $writer->flush();

        if (0 !== $writtenElements) {
            // Finalizing document with $writer->endDocument() adds closing tags for all bpt-ept tags
            // so add body and tmx closing tags manually
            file_put_contents($resultFilepath, PHP_EOL . '</body>', FILE_APPEND);
        } else {
            file_put_contents($resultFilepath, PHP_EOL . '/>', FILE_APPEND);
        }

        file_put_contents($resultFilepath, PHP_EOL . '</tmx>', FILE_APPEND);

        $brokenTranslationUnitIndicator->writeCollectedTUsLog();

        return $resultFilepath;
    }

    private function getProcessorsChain(
        Language $sourceLang,
        Language $targetLang,
        ImportOptions $importOptions,
    ): ?TmxImportProcessor {
        /** @var TmxImportProcessor[] $processors */
        $processors = [];

        foreach ($this->tmxImportProcessors as $importProcessor) {
            if ($importProcessor->supports($sourceLang, $targetLang, $importOptions)) {
                $processors[$importProcessor->order()] = $importProcessor;
            }
        }

        krsort($processors);

        if (empty($processors)) {
            return null;
        }

        $processors = array_values($processors);
        $nextProcessor = null;
        $processor = null;

        foreach ($processors as $processor) {
            if ($nextProcessor !== null) {
                $processor->setNext($nextProcessor);
            }

            $nextProcessor = $processor;
        }

        return $processor;
    }
}
