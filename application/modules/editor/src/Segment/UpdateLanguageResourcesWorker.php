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

namespace MittagQI\Translate5\Segment;

use editor_Models_Segment;
use editor_Models_Segment_MatchRateType;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\RescheduleUpdateNeededException;
use MittagQI\Translate5\LanguageResource\Operation\UpdateSegmentOperation;
use MittagQI\Translate5\Task\TaskEventTrigger;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Worker_Abstract;

class UpdateLanguageResourcesWorker extends ZfExtended_Worker_Abstract
{
    private editor_Models_Segment $segment;

    protected function validateParameters(array $parameters): bool
    {
        if (empty($parameters['segmentId'])) {
            return false;
        }

        $this->segment = ZfExtended_Factory::get(editor_Models_Segment::class);

        try {
            $this->segment->load($parameters['segmentId']);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return false;
        }

        return true;
    }

    protected function work(): bool
    {
        if (! editor_Models_Segment_MatchRateType::isUpdatable($this->segment->getMatchRateType())) {
            return true;
        }

        try {
            UpdateSegmentOperation::create()->updateSegment($this->segment);
        } catch (RescheduleUpdateNeededException) {
            // Wait a little bit and reschedule self for next try
            sleep(30);
            (new TaskEventTrigger())->triggerAfterSegmentUpdate($this->segment->getTask(), $this->segment);

            return false;
        }

        return true;
    }
}
