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
use editor_Models_Segment;
use ZfExtended_Models_Entity_NotFoundException;

/**
 *
 */
class Mid extends AlignmentAbstract
{

    /**
     * @param editor_Models_Import_FileParser $parser
     * @return editor_Models_Segment|null
     */
    public function findSegment(editor_Models_Import_FileParser $parser): ?editor_Models_Segment
    {
        $this->initSegment($parser->getTask()->getTaskGuid());
        $mid = $parser->getMid();
        try {

            $this->getSegment()->loadByFileidMid($parser->getFileId(), $mid);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->addError(new Error(
                'E1434',//TODO: new event code if needed in future
                'Reimport Segment processor: No matching segment was found for the given mid.',
                [$mid]
            ));
            return null;
        }
        return $this->getSegment();
    }
}