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

namespace MittagQI\Translate5\T5Memory\TmxImportPreprocessor;

use editor_Models_Languages as Language;
use MittagQI\Translate5\Segment\SegmentationService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Exception\BrokenTranslationUnitException;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger;
use MittagQI\Translate5\TMX\TransUnitParser;
use MittagQI\Translate5\TMX\TransUnitStructure;
use Zend_Config;
use Zend_Registry;

class TranslationUnitResegmentProcessor extends Processor
{
    public const RESEGMENT_TU_OPTION = 'resegmentTmx';

    public function __construct(
        private readonly SegmentationService $segmentationService,
        private readonly Zend_Config $config,
        private readonly TransUnitParser $transUnitParser,
    ) {
    }

    public static function create(): self
    {
        return new self(
            SegmentationService::create(),
            Zend_Registry::get('config'),
            new TransUnitParser(),
        );
    }

    public function supports(Language $sourceLang, Language $targetLang, ImportOptions $importOptions): bool
    {
        return $importOptions->resegmentTmx;
    }

    public function order(): int
    {
        return 100;
    }

    protected function processTu(
        string $tu,
        Language $sourceLang,
        Language $targetLang,
        ImportOptions $importOptions,
        BrokenTranslationUnitLogger $brokenTranslationUnitIndicator,
    ): iterable {
        try {
            $structure = $this->transUnitParser->extractStructure(
                $tu,
                $sourceLang,
                $targetLang,
            );
        } catch (BrokenTranslationUnitException) {
            if ($this->config->runtimeOptions->tmxImportProcessor?->debug) {
                error_log("Trans unit has unexpected structure and was excluded from TMX import:\n" . $tu);
            }

            $brokenTranslationUnitIndicator->logProblemOnce();

            return yield from [];
        }

        $sourceSegments = $this->segmentationService->splitTextToSegments(
            $structure->source,
            $sourceLang->getRfc5646(),
            $importOptions->customerId,
        );

        $targetSegments = $this->segmentationService->splitTextToSegments(
            $structure->target,
            $targetLang->getRfc5646(),
            $importOptions->customerId,
        );

        if (count($sourceSegments) !== count($targetSegments)) {
            if ($this->config->runtimeOptions->tmxImportProcessor?->debug) {
                error_log("Source and target segments do not match in number, skipping TU:\n" . $tu);
            }

            return yield $tu;
        }

        if (count($sourceSegments) === 1 && count($targetSegments) === 1) {
            return yield $tu;
        }

        foreach ($sourceSegments as $index => $sourceSegment) {
            yield str_replace(
                [
                    TransUnitStructure::SOURCE_PLACEHOLDER,
                    TransUnitStructure::TARGET_PLACEHOLDER,
                ],
                [
                    trim($sourceSegment, ' '),
                    trim($targetSegments[$index], ' '),
                ],
                $structure->template,
            );
        }
    }
}
