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

namespace MittagQI\Translate5\Segment\Repetition;

use editor_Models_Segment as Segment;
use editor_Models_Segment_InternalTag;
use editor_Models_SegmentFieldManager;
use ZfExtended_Utils;

/**
 * Segment Repetition Tag Replacer
 * when processing repetitions (change alikes) the contained tags in the content must be replaced by the tags which were before in the segment.
 */
class RepetitionUpdater
{
    public function __construct(
        private readonly editor_Models_Segment_InternalTag $tagHelper,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new editor_Models_Segment_InternalTag(),
        );
    }

    /**
     * Updates the target fields in the repeated segment with the content
     * of the original segment with the tags replaced with the previous tags (in the repeated)
     * @param bool $useSourceTags if true, force usage tags from source instead from target
     */
    public function updateTarget(Segment $master, Segment $repetition, bool $useSourceTags = false): bool
    {
        $originalContent = $useSourceTags ? '' : $repetition->getTarget();
        $segmentContent = $master->getTargetEdit();

        $originalContent = $this->checkAndGetSegmentContent($repetition, $originalContent);

        return $this->tagHelper->updateSegmentContent(
            $originalContent,
            $segmentContent,
            function ($originalContent, $segmentContent) use ($master, $repetition) {
                $repetition->setTargetEdit($segmentContent);
                // when copying targets originating from a
                // language-resource, we copy the original target as well ...
                if ($master->isFromLanguageResource()) {
                    $originalContent = $this->checkAndGetSegmentContent($repetition, $originalContent);
                    $this->tagHelper->updateSegmentContent(
                        $originalContent,
                        $master->getTarget(),
                        function ($originalContent, $segmentContent) use ($repetition) {
                            $repetition->setTarget($segmentContent);
                            $repetition->updateToSort('target');
                        }
                    );
                }
                $repetition->updateToSort('target' . editor_Models_SegmentFieldManager::_EDIT_SUFFIX);
            }
        );
    }

    /**
     * Updates the non-editable source field to take over the term markup into the repetition.
     * If modifying the editable source, whitespace should be ignored,
     * if repeating the source all tags must be taken over.
     */
    public function updateSource(Segment $master, Segment $repetition, bool $editable): bool
    {
        $segmentContent = $editable ? $master->getSourceEdit() : $master->getSource();

        $originalContent = $this->checkAndGetSegmentContent($repetition, $repetition->getSource());

        return $this->tagHelper->updateSegmentContent(
            $originalContent,
            $segmentContent,
            function ($originalContent, $segmentContent) use ($editable, $repetition) {
                $toSort = 'source';
                if ($editable) {
                    $repetition->setSourceEdit($segmentContent);
                    $toSort .= editor_Models_SegmentFieldManager::_EDIT_SUFFIX;
                } else {
                    $repetition->setSource($segmentContent);
                }
                $repetition->updateToSort($toSort);
            },
            $editable
        );
    }

    /**
     * Check and validate the given segment original content.
     * In case the given content is empty, the repeated segment source will be returned.
     */
    private function checkAndGetSegmentContent(Segment $repetition, string $originalContent): string
    {
        if (ZfExtended_Utils::emptyString($originalContent)) {
            return $repetition->getSource();
        }

        return $originalContent;
    }
}
