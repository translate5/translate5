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

namespace MittagQI\Translate5\Task\Import\Alignment;

use editor_Models_Import_FileParser;
use editor_Models_Import_SegmentProcessor_RelaisSourceCompare;
use editor_Models_Segment;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class Source extends AlignmentAbstract
{
    public int $segmentNrInTask = 0;

    private editor_Models_Import_SegmentProcessor_RelaisSourceCompare $sourceCompare;

    public function __construct()
    {
        parent::__construct();
        $this->sourceCompare = ZfExtended_Factory::get(
            editor_Models_Import_SegmentProcessor_RelaisSourceCompare::class
        );
    }

    public function findSegment(editor_Models_Import_FileParser $parser): ?editor_Models_Segment
    {
        $data = $parser->getFieldContents();
        $source = $parser->getSegmentFieldManager()->getFirstSourceName();
        $mid = $parser->getMid();
        $loadBySegmentNr = false;
        $taskGuid = $parser->getTask()->getTaskGuid();

        $this->segmentNrInTask++;

        $this->initSegment($taskGuid);

        try {
            //try loading via fileId and Mid
            $this->getSegment()->loadByFileidMid($parser->getFileId(), $mid);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //if above was not successful, load via segmentNrInTask (the mid is only for logging!)
            $loadBySegmentNr = $this->loadSegmentByNrInTask($parser->getMid(), $taskGuid);
            if (! $loadBySegmentNr) {
                //if no segment was found via segmentNr, we ignore it
                return null;
            }
        }
        $contentIsEqual = $this->sourceCompare->isEqual(
            $this->getSegment()->getFieldOriginal($source),
            $data[$source]['original']
        );

        //if content is not equal, but was loaded with mid, try to load with segment nr and compare again
        if (! $contentIsEqual && ! $loadBySegmentNr) {
            //load via segmentNrInTask (the mid is only for logging!)
            if (! $this->loadSegmentByNrInTask($mid, $taskGuid)) {
                return null;
            }
            $contentIsEqual = $this->sourceCompare->isEqual(
                $this->getSegment()->getFieldOriginal($source),
                $data[$source]['original']
            );
        }

        //if source and relais content is finally not equal, we log that and ignore the segment
        if (! $contentIsEqual) {
            $extra = 'mid: ' . $parser->getMid() .
                ' / Source content of processing file: ' .
                $this->getSegment()->getFieldOriginal($source) .
                ' / Source content of original file: ' .
                $data[$source]['original'];

            $this->addError(new Error(
                'E1021',
                'Source-content of the processing file "{fileName}" is not identical with source of original file. See Details.',
                [$extra]
            ));
        }

        return $contentIsEqual ? $this->getSegment() : null;
    }

    /**
     * Tries to load the segment to current relais content via segmentNrInTask
     * returns true if found a segment, false if not. If false this is logged.
     */
    protected function loadSegmentByNrInTask(string $mid, string $taskGuid): bool
    {
        try {
            $this->getSegment()->loadBySegmentNrInTask($this->segmentNrInTask, $taskGuid);

            return true;
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->addError(new Error(
                'E1020',
                'The following MIDs are present in the processing file "{fileName}" but
             could not be found in the original file, the segment(s) was/were ignored.﻿ See Details.',
                [$mid]
            ));

            return false;
        }
    }
}
