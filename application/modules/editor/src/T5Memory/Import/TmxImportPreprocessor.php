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

namespace MittagQI\Translate5\T5Memory\Import;

use editor_Models_Languages as Language;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\T5Memory\Api\Contract\TmxImportPreprocessorInterface;
use MittagQI\Translate5\T5Memory\Contract\TmxImportProcessor;
use MittagQI\Translate5\T5Memory\DirectoryPath;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Exception\UnableToCreateFileForTmxPreprocessingException;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\AddFakeContextProcessor;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\CapitaliseAuthorProcessor;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\ContentProtectionProcessor;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\FixCreationTimeProcessor;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\RemoveCompromisedSegmentsProcessor;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\RemoveDifferentLanguageNodesProcessor;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\TranslationUnitResegmentProcessor;
use MittagQI\Translate5\T5Memory\TMX\TmxSymbolsFixer;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\Contract\BrokenTranslationUnitLoggerInterface;
use MittagQI\Translate5\TMX\Filter\TmxFilter;
use XMLWriter;

class TmxImportPreprocessor implements TmxImportPreprocessorInterface
{
    /**
     * @param iterable<TmxImportProcessor> $tmxImportProcessors
     */
    public function __construct(
        private readonly LanguageRepository $languageRepository,
        private readonly iterable $tmxImportProcessors,
        private readonly TmxFilter $tmxFilter,
        private readonly TmxSymbolsFixer $symbolsFixer,
        private readonly DirectoryPath $directoryPath,
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
                RemoveDifferentLanguageNodesProcessor::create(),
                RemoveCompromisedSegmentsProcessor::create(),
                ContentProtectionProcessor::create(),
                TranslationUnitResegmentProcessor::create(),
                AddFakeContextProcessor::create(),
                CapitaliseAuthorProcessor::create(),
                FixCreationTimeProcessor::create(),
            ],
            TmxFilter::create(),
            TmxSymbolsFixer::create(),
            DirectoryPath::create(),
        );
    }

    public function process(
        string $filepath,
        int $sourceLangId,
        int $targetLangId,
        ImportOptions $importOptions,
        BrokenTranslationUnitLoggerInterface $brokenTranslationUnitLogger,
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

        $processingDir = $this->directoryPath->tmxImportProcessingDir();

        $this->symbolsFixer->fixInvalidXmlSymbols($filepath);

        $processedFilename = str_replace('.tmx', '', basename($filepath)) . '_processed.tmx';

        $resultFilepath = $processingDir . $processedFilename;

        $writer = new XMLWriter();

        if (! $writer->openURI($resultFilepath)) {
            throw new UnableToCreateFileForTmxPreprocessingException($resultFilepath);
        }

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        // suppress: namespace error : Namespace prefix t5 on n is not defined
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        foreach ($this->tmxFilter->filter($filepath, $importOptions->tmxFilterOptions) as [$node, $isTu]) {
            if (! $isTu) {
                $writer->writeRaw($node);

                continue;
            }

            $tus = $processorChain->process(
                $node,
                $sourceLang,
                $targetLang,
                $importOptions,
                $brokenTranslationUnitLogger,
            );

            foreach ($tus as $transUnit) {
                if (empty($transUnit)) {
                    continue;
                }

                $writer->writeRaw($transUnit);
            }
        }

        error_reporting($errorLevel);

        $writer->flush();

        $brokenTranslationUnitLogger->writeCollectedTUsLog();

        // after protection there may be new duplicates
        $this->tmxFilter->filterFile($resultFilepath, $importOptions->tmxFilterOptions);

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
