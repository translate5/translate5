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

namespace MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor;

use editor_Models_Languages as Language;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\Contract\BrokenTranslationUnitLoggerInterface;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\TranslationUnitCollector\CleanupSegmentCollector;

class RemovePropsFromSegLevelProcessor extends Processor
{
    public function __construct(
    ) {
    }

    public static function create(): self
    {
        return new self(
        );
    }

    public function supports(Language $sourceLang, Language $targetLang, ImportOptions $importOptions): bool
    {
        return true;
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
        BrokenTranslationUnitLoggerInterface $brokenTranslationUnitIndicator,
    ): iterable {
        //    <tu tuid="1" creationdate="20260127T081449Z" creationid="TRANS LATOR ONE">
        //      <prop type="tmgr:docname">Word 124.docx</prop>
        //      <prop type="tmgr:context">-</prop>
        //      <tuv xml:lang="de">
        //        <seg><prop type="user-def">segId1</prop>Hallo Welt,</seg>
        //      </tuv>
        //      <tuv xml:lang="en">
        //        <seg><prop type="user-def">segId1</prop>Hello world,</seg>
        //      </tuv>
        //    </tu>

        // Remove <prop> tags from within <seg> elements
        $tu = preg_replace_callback(
            '#(<seg[^>]*>)(.*?)(</seg>)#s',
            function ($matches) use ($tu, $brokenTranslationUnitIndicator) {
                $openTag = $matches[1];
                $content = $matches[2];
                $closeTag = $matches[3];

                // Remove all <prop>...</prop> tags from segment content
                $replacedContent = preg_replace('#<prop[^>]*>.*?</prop>#s', '', $content);

                if ($content !== $replacedContent) {
                    $brokenTranslationUnitIndicator->collectProblematicTU(
                        CleanupSegmentCollector::logCode(),
                        $tu,
                        [
                            CleanupSegmentCollector::ENTITIES_KEY => ['<prop> tag'],
                        ]
                    );
                }

                return $openTag . $replacedContent . $closeTag;
            },
            $tu
        );

        return yield $tu;
    }
}
