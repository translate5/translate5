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

namespace MittagQI\Translate5\Segment\SearchAndReplace;

use editor_Models_Segment_InternalTag;
use editor_Models_Segment_TermTag;
use editor_Models_Segment_TrackChangeTag as TrackChangeTag;

/**
 * This class is used to replace the content between two:
 *  - ranges: the ranges are provided by @see FindMatchesHtml
 *  - indexes: start and end index
 * in string/segment with or without html tags in it.
 * Unneeded content between those ranges will be removed or moved after the replace string @see self::assembleReplaceContent
 */
class ReplaceMatchesSegment
{
    /**
     * internal protected tag regex
     */
    public const REGEX_PROTECTED_INTERNAL = '/<translate5:escaped[^>]+((id="([^"]*)"[^>]))[^>]*>/';

    private function __construct(
        private readonly editor_Models_Segment_InternalTag $internalTagHelper,
        private readonly editor_Models_Segment_TermTag $termTag,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new editor_Models_Segment_InternalTag(),
            new \editor_Models_Segment_TermTag(),
        );
    }

    /**
     * Find and replace the matches in the segment text
     */
    public function replaceText(
        string $segmentText,
        string $queryString,
        string $replaceText,
        string $searchType,
        TrackChangeTag $trackChangeTag,
        bool $isActiveTrackChanges = false,
        bool $matchCase = false,
    ): string {
        //protect the tags and remove the terms
        $segmentText = $this->protectTags($segmentText, $trackChangeTag);

        //find matches in the segment
        $html = new FindMatchesHtml($segmentText);
        $html->matchCase = $matchCase;

        //find match ranges in the original segment text
        $replaceRanges = $html->findContent($queryString, $searchType);

        //merge the replace string in the segment
        $segmentText = $this->assembleReplaceContent(
            $segmentText,
            $replaceRanges,
            $replaceText,
            $isActiveTrackChanges,
            $trackChangeTag
        );

        //unprotect the tags
        return $this->unprotectTags($segmentText, $trackChangeTag);
    }

    /**
     * Insert the replace text in the segment text based on the given range.
     */
    private function assembleReplaceContent(
        string $segmentText,
        array $replaceRanges,
        string $replaceText,
        bool $isActiveTrackChanges,
        TrackChangeTag $trackChangeTag,
    ): string {
        $rangesOffset = 0;

        foreach ($replaceRanges as $range) {
            //update the ranges with given offset
            $range['start'] = $range['start'] + $rangesOffset;
            $range['end'] = $range['end'] + $rangesOffset;

            //handle delete tags in range
            preg_match_all(
                TrackChangeTag::REGEX_PROTECTED_DEL,
                $range['text'],
                $tempMatchesProtectedDel,
                PREG_OFFSET_CAPTURE
            );

            foreach ($tempMatchesProtectedDel[0] as $match) {
                //remove the protected del tag conteng from the array if matches
                $trackChangeTag->updateOriginalTagValue($match[0], '');
            }

            $stackEnd = [];
            //handle internal tags in range
            preg_match_all(
                self::REGEX_PROTECTED_INTERNAL,
                $range['text'],
                $tempMatchesProtectedInternal,
                PREG_OFFSET_CAPTURE
            );

            foreach ($tempMatchesProtectedInternal[0] as $match) {
                $stackEnd[] = $match[0];
            }

            //open insert tag is found
            $insOpen = false;
            //insert tag at end of the text
            $insAtEnd = '';
            //all tags to be placed at the beginning of the string
            $stackStart = [];

            //merge the insert tags in the replacement range
            //remove pair tags (start and end ins in the range)
            //move unpaired end tags at the beginning of the replace string
            //move unpaired start tags at the end of the replace string
            $rangePiece = preg_replace_callback(
                TrackChangeTag::REGEX_INS,
                function ($match) use (&$insOpen, &$stackStart, &$insAtEnd) {
                    //if the replacement range is in the midle of an ins
                    //ex. Aleksandar </ins> mitrev (move the tag at the begining of the replace text) ->  </ins>Aleksandar Mitrev
                    if (! $insOpen && strtolower($match[0]) === '</ins>') {
                        $stackStart[] = $match[0];

                        return '';
                    }

                    //if the match is an open ins tag, collect the tag
                    if (substr(strtolower($match[0]), 0, 5) === '<ins ') {
                        $insOpen = true;
                        $insAtEnd = $match[0];

                        return '';
                    }
                    //if it is an end ins tag, the paired tag is reached
                    if (strtolower($match[0]) === '</ins>') {
                        $insAtEnd = '';
                        $insOpen = false;

                        return '';
                    }

                    return '';
                },
                $range['text']
            );

            //create the replace delete part
            $deletePart = implode('', $stackStart) . $this->getDeletePart($rangePiece, $trackChangeTag);

            //create the replace ins part
            $insertPart = $this->getInsertPart($replaceText . implode('', $stackEnd), $trackChangeTag) . $insAtEnd;

            $str = $deletePart . $insertPart;

            //calculate the offset
            $rangesOffset += strlen($str) - $range['length'];

            //insert the text at the position
            $segmentText = $this->insertTextAtRange($segmentText, $str, $range['start'], $range['end']);
        }

        return $segmentText;
    }

    /**
     * Protect the internal tags and the del tags from the segment text.
     * Remove the terms from the segment text.
     */
    private function protectTags(string $segmentText, TrackChangeTag $trackChangeTag): string
    {
        //remove the terms from the string. The term tagger should be started before the segment is saved
        $segmentText = $this->termTag->remove($segmentText, true);
        $segmentText = $this->internalTagHelper->protect($segmentText);

        return $trackChangeTag->protect($segmentText);
    }

    private function unprotectTags(string $segmentText, TrackChangeTag $trackChangeTag)
    {
        $segmentText = $this->internalTagHelper->unprotect($segmentText);

        return $trackChangeTag->unprotect($segmentText);
    }

    /**
     * Insert the text at range and return the result
     *
     * @param string $text    - text to be inserted
     * @param int $startIndex - start index
     * @param int $endIndex   - end index
     */
    private function insertTextAtRange(
        string $segmentText,
        string $text,
        int $startIndex,
        int $endIndex,
    ): string {
        $partOne = substr($segmentText, 0, $startIndex);
        $partTwo = substr($segmentText, $endIndex);

        return $partOne . $text . $partTwo;
    }

    private function getDeletePart(string $deleteText, TrackChangeTag $trackChangeTag): string
    {
        if ($deleteText === '') {
            return '';
        }

        return $trackChangeTag->createTrackChangesNode(
            TrackChangeTag::NODE_NAME_DEL,
            $deleteText
        );
    }

    private function getInsertPart(string $insertText, TrackChangeTag $trackChangeTag): string
    {
        if ($insertText === '') {
            return $insertText;
        }

        return $trackChangeTag->createTrackChangesNode(
            TrackChangeTag::NODE_NAME_INS,
            $insertText
        );
    }
}
