<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Task\Overview\SegmentFormatter;

use editor_Models_Task as Task;
use MittagQI\Translate5\Repository\TaskUserTrackingRepository;
use ZfExtended_Zendoverwrites_Translate;

class TrackChangesTagFormatter implements SegmentFormatterInterface
{
    public function __construct(
        private readonly TaskUserTrackingRepository $taskUserTrackingRepository,
        private readonly ZfExtended_Zendoverwrites_Translate $translate,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new TaskUserTrackingRepository(),
            ZfExtended_Zendoverwrites_Translate::getInstance(),
        );
    }

    public function __invoke(Task $task, string $segment): string
    {
        $tracks = $this->taskUserTrackingRepository->getByTaskGuid($task->getTaskGuid());
        $trackChangeIdToUserName = array_column($tracks, 'userName', 'id');

        //remove full tags
        $matchResult = preg_match_all('/<(ins|del).+>/mU', $segment, $changes, PREG_SET_ORDER);

        if ($matchResult === false || $matchResult === 0) {
            return $segment;
        }

        foreach ($changes as $change) {
            preg_match('/data-usertrackingid="([^"]+)"/', $change[0], $userTrackingIdMatches);
            preg_match('/data-timestamp="([^"]+)"/', $change[0], $timestampMatches);

            $userTrackingId = $userTrackingIdMatches[1];
            $userName = $trackChangeIdToUserName[$userTrackingId] ?? '';
            $tag = $change[1] === 'ins' ? 'ins' : 'del';

            $segment = str_replace(
                $change[0],
                sprintf(
                    '<%s data-message="%s %s - %s">',
                    $tag,
                    $this->translate->_('Inserted by'),
                    $userName,
                    $timestampMatches[1]
                ),
                $segment
            );
        }

        //replace short tag div span construct to a simple span
        return $segment;
    }
}
