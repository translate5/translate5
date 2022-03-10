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

/**
 * calculates and removes leading and trailing paired and special single tags
 * removing means: the tags are not imported, they are added directly to the skeleton file and are not changeable therefore
 */
class editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_All extends editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Abstract {

    /**
     * Flag if any tag was found in the removable content
     * @var bool
     */
    protected bool $foundTags = false;

    /**
     * calculates the tags to be cut off, returns true if there is content to be cut of
     * @param array $sourceChunks
     * @param array $targetChunks
     * @return bool
     */
    protected function _calculate(array $sourceChunks, array $targetChunks): bool
    {
        $this->foundTags = false;

        // algorithm for complete chunking:
        // <1><2>TEXT</2><3/>BLA</1><4><5/></4>
        // get same source / target tags from start till text; <1><2> (still the tags must be the same in source and target!)
        $trimmedFromStart = $this->findRemovableContent($sourceChunks, $targetChunks);

        // we have to slice away the already trimmed content, otherwise we would get trouble if there is no text at all!
        $fromStartCount = count($trimmedFromStart);

        // get tags from end till text; </1><4><5/></4>
        $trimmedFromEnd = $this->findRemovableContent(array_slice($sourceChunks, $fromStartCount), array_slice($targetChunks, $fromStartCount), false);

        $allTags = array_filter(array_merge($trimmedFromStart, $trimmedFromEnd), function($item){
            return $item instanceof editor_Models_Import_FileParser_Tag;
        });

        // loop start tags from start and check if isSingle, or if tag partner is in start + end tag list. If yes tag can be sliced, if no stop.
        // in the example <1><2> should be trimmed, but since <2> is not in the ended trimmed content,
        //  we have to stop trimming after <1>
        $this->startShiftCount = $this->partnerExists($trimmedFromStart, $allTags);

        // same from end (tags are already in reverse order!)
        $this->endShiftCount = $this->partnerExists($trimmedFromEnd, $allTags);

        return $this->foundTags;
    }

    /**
     * checks if on paired tags the partner is also removed, if not, we have to stop here and keep that tag
     * @param array $trimmedContent
     * @param array $allTags
     * @return int
     */
    private function partnerExists(array $trimmedContent, array $allTags): int {
        $counter = 0;
        foreach($trimmedContent as $chunk) {
            if($chunk instanceof editor_Models_Import_FileParser_Tag && !$chunk->isSingle()) {
                // if we get here, its either an opener or a closer, so check the partner!
                if(!is_null($chunk->partner) && !in_array($chunk->partner, $allTags)) {
                    // if we have a partner, which is not in the trimmed content (like <2> in the above content)
                    return $counter;
                }
                //if come here, it is sure that there is at least one tag to be removed, so we found one
                if(!$this->foundTags) {
                    $this->foundTags = true;
                }
            }
            $counter++;
        }
        return $counter;
    }

    /**
     * finds the content to be removed from source and target chunks and returns it
     * @param array $source
     * @param array $target
     * @param bool $fromStart true if trim from start, false to trim from end
     * @return array the chunks trimmed from start or end
     */
    protected function findRemovableContent(array $source, array $target, bool $fromStart = true): array {
        $trimmed = [];
        do {
            if($fromStart) {
                $sourceChunk = array_shift($source);
                $targetChunk = array_shift($target);
            }
            else {
                $sourceChunk = array_pop($source);
                $targetChunk = array_pop($target);
            }
            //make a string cast here, so that the rendered tag content is used of tags (tags are objects here)
            if((string)$sourceChunk !== (string)$targetChunk) {
                return $trimmed;
            }
            //inc internal start shift count
            $isEmptyString = is_string($sourceChunk) && strlen($sourceChunk) === 0;
            $isTag = ($sourceChunk ?? null) instanceof editor_Models_Import_FileParser_Tag;
            $isWhitespace = (preg_match('#^\s+$#', $sourceChunk ?? ''));
            $toBeTrimmed = $isWhitespace || $isTag || $isEmptyString;
            if($toBeTrimmed) {
                $trimmed[] = $sourceChunk;
            }
        }
        while($toBeTrimmed);

        return $trimmed;
    }
}