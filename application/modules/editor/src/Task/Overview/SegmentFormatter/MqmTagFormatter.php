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

use editor_Models_Segment_Mqm;
use editor_Models_Task as Task;
use ZfExtended_Zendoverwrites_Translate;

class MqmTagFormatter implements SegmentFormatterInterface
{
    public function __construct(
        private readonly editor_Models_Segment_Mqm $mqmConverter,
        private readonly ZfExtended_Zendoverwrites_Translate $translate,
        private readonly string $messageAttr,
        private readonly string $color,
    ) {
    }

    public static function create(string $messageAttr = 'data-message', string $color = '#ff821596'): self
    {
        return new self(
            new editor_Models_Segment_Mqm(),
            ZfExtended_Zendoverwrites_Translate::getInstance(),
            $messageAttr,
            $color,
        );
    }

    public function __invoke(Task $task, string $segment): string
    {
        $resultRenderer = function (
            string $part,
            array $cls,
            string $issueId,
            string $issueName,
            string $sev,
            string $sevName,
            string $comment,
        ): string {
            $title = empty($sevName) ? '' : htmlspecialchars($this->translate->_($sevName)) . ': ';
            $title .= htmlspecialchars(empty($issueName) ? '' : $this->translate->_($issueName));
            $title .= empty($comment) ? '' : ' / ' . $comment;

            $span = '<span style="background-color:' . $this->color . '" ' . $this->messageAttr . '="%1$s"> %2$s </span>';
            if (in_array('open', $cls)) {
                return sprintf($span, $title, '[' . $issueId);
            }

            return sprintf($span, $title, $issueId . ']');
        };

        return $this->mqmConverter->replace($task, $segment, $resultRenderer);
    }
}
