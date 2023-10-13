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

use MittagQI\Translate5\NumberProtection\Model\NumberFormatRepository;
use MittagQI\Translate5\NumberProtection\NumberProtector;

class editor_Services_Connector_TagHandler_T5MemoryXliff extends editor_Services_Connector_TagHandler_Xliff
{
    private const T5MEMORY_NUMBER_TAG = 't5:n';
    protected const ALLOWED_TAGS = '<x><x/><bx><bx/><ex><ex/><g><number>';
    private NumberFormatRepository $numberFormatRepository;
    private NumberProtector $numberProtector;
    private array $numberTagMap = [];

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->numberFormatRepository = new NumberFormatRepository();
        $this->numberProtector = NumberProtector::create();
        $this->xmlparser->registerElement(NumberProtector::TAG_NAME, null, function ($tagName, $key, $opener) {
            $this->xmlparser->replaceChunk($key, function () use ($key) {
                return $this->numberProtector->convertToInternalTagsWithShortcutNumberMap(
                    $this->xmlparser->getChunk($key),
                    $this->shortTagIdent,
                    $this->shortcutNumberMap
                );
            });
        });
    }

    public function restoreInResult(string $resultString): ?string
    {
        $t5nTagRegex = sprintf('/<%s id="(\d+)" n="(.+)" r="(.+)"\s?\/>/Uu', self::T5MEMORY_NUMBER_TAG);

        if (preg_match_all($t5nTagRegex, $resultString, $matches, PREG_SET_ORDER)) {
            $numberTags = [];
            foreach ($matches as $match) {
                // $numberTags[*r*][*id*] = ['number' => *n*, 'tag' => *wholeTag*];
                $numberTags[$match[3]][$match[1]] = ['number' => $match[2], 'tag' => $match[0]];
            }

            foreach ($numberTags as $regex => $tags) {
                // sort tags by their ids
                ksort($tags);

                if (isset($this->numberTagMap[$regex])) {
                    $resultString = str_replace(array_column($tags, 'tag'), $this->numberTagMap[$regex], $resultString);

                    continue;
                }

                foreach ($tags as ['number' => $number, 'tag' => $tag]) {
                    $resultString = str_replace($tag, $number, $resultString);
                }
            }
        }

        return parent::restoreInResult($resultString);
    }

    protected function convertQuery(string $queryString, bool $isSource): string
    {
        $queryString = $this->contentProtector->unprotect($queryString, NumberProtector::alias());
        $regex = NumberProtector::fullTagRegex();

        if (!preg_match_all($regex, $queryString, $tags, PREG_SET_ORDER)) {
            return $queryString;
        }

        $currentId = 1;
        foreach ($tags as $tagProps) {
            $tag = array_shift($tagProps);
            $tagProps = array_combine(['type', 'name', 'source', 'iso', 'target'], $tagProps);

            $numberRecognition = $this->numberFormatRepository->getNumberRecognition(
                $tagProps['type'],
                $tagProps['name']
            );

            $encodedRegex = base64_encode(gzdeflate($numberRecognition->getRegex()));
            $t5nTag = sprintf(
                '<%s id="%s" n="%s" r="%s"/>',
                self::T5MEMORY_NUMBER_TAG,
                $currentId,
                $isSource ? $tagProps['source'] : $tagProps['target'],
                $encodedRegex
            );

            $this->numberTagMap[$encodedRegex][] = $tag;

            $queryString = str_replace($tag, $t5nTag, $queryString);
            $currentId++;
        }

        return $queryString;
    }

    protected function processXliffTags(string $queryString): string
    {
        if ($this->gTagPairing) {
            return $this->utilities->internalTag->toXliffPaired($queryString, replaceMap: $this->map,);
        }

        return $this->utilities->internalTag->toXliff(
            $queryString,
            replaceMap: $this->map,
            dontRemoveTags: ['<' . self::T5MEMORY_NUMBER_TAG . '>']
        );
    }
}
