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

use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\ContentProtection\T5memory\T5NTag;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;

class editor_Services_Connector_TagHandler_T5MemoryXliff extends editor_Services_Connector_TagHandler_Xliff
{
    protected const ALLOWED_TAGS = '<x><x/><bx><bx/><ex><ex/><g><number>';

    private NumberProtector $numberProtector;

    private TmConversionService $conversionService;

    /**
     * @var array<string, array<string, \SplQueue<int>>>
     */
    private array $numberTagMap = [];

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->conversionService = TmConversionService::create();
        $this->numberProtector = NumberProtector::create();
        $this->xmlparser->registerElement(NumberProtector::TAG_NAME, null, function ($tagName, $key, $opener) {
            $this->xmlparser->replaceChunk($key, function () use ($key) {
                return $this->numberProtector->convertToInternalTagsWithShortcutNumberMap(
                    $this->xmlparser->getChunk($key),
                    $this->shortTagIdent,
                    $this->shortcutNumberMap
                )->segment;
            });
        });
    }

    public function restoreInResult(string $resultString, bool $isSource = true): ?string
    {
        $t5nTagRegex = T5NTag::fullTagRegex();

        if (preg_match_all($t5nTagRegex, $resultString, $matches, PREG_SET_ORDER)) {
            $numberTags = [];
            foreach ($matches as $match) {
                // $numberTags[*r*][*id*] = ['number' => *n*, 'tag' => *wholeTag*];
                $numberTags[$match[2]][$match[1]] = [
                    'number' => html_entity_decode($match[3]),
                    'tag' => $match[0],
                ];
            }

            foreach ($numberTags as $rule => $tags) {
                // sort tags by their ids
                ksort($tags);
                // get rid of the keys
                $tags = array_values($tags);

                if (isset($this->numberTagMap[$rule])) {
                    $taskSegmentTags = [];

                    foreach ($this->numberTagMap[$rule] as $tag => $ids) {
                        foreach ($ids as $id) {
                            $taskSegmentTags[$id] = $tag;
                        }
                    }

                    ksort($taskSegmentTags);
                    $taskSegmentTags = array_values($taskSegmentTags);

                    foreach ($taskSegmentTags as $id => $segmentTag) {
                        if (! isset($tags[$id])) {
                            break;
                        }

                        $resultString = str_replace($tags[$id]['tag'], $segmentTag, $resultString);

                        unset($tags[$id]);
                    }

                    if (empty($tags)) {
                        continue;
                    }
                }

                foreach ($tags as ['number' => $number, 'tag' => $tag]) {
                    $resultString = str_replace($tag, $number, $resultString);
                }
            }
        }

        return parent::restoreInResult($resultString, $isSource);
    }

    protected function convertQuery(string $queryString, bool $isSource): string
    {
        $this->numberTagMap = [];

        return $this->conversionService->convertContentTagToT5MemoryTag($queryString, $isSource, $this->numberTagMap);
    }

    protected function processXliffTags(string $queryString): string
    {
        if ($this->gTagPairing) {
            return $this->utilities->internalTag->toXliffPaired($queryString, replaceMap: $this->map);
        }

        return $this->utilities->internalTag->toXliff(
            $queryString,
            replaceMap: $this->map,
            dontRemoveTags: ['<' . T5NTag::TAG . '>']
        );
    }
}
