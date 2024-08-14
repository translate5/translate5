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

class ReplaceInternalTagWithSpanFormatter implements SegmentFormatterInterface
{
    public function __construct(
        private readonly string $messageAttr = 'data-message',
        private readonly string $color = 'rgba(207, 207, 207, 0.667)'
    ) {
    }

    public function __invoke(Task $task, string $segment): string
    {
        //remove full tags
        $segment = preg_replace('#<span[^>]+class="full"[^>]*>[^<]*</span>#i', '', $segment);

        //replace short tag div span construct to a simple span
        return preg_replace(
            '#<div[^>]+>\s*<span\s*class="short"\s*title="(.+)"([^>]*)>([^<]*)</span>[\s]*</div>#miU',
            '<span ' . $this->messageAttr . '="$1" style="background-color: ' . $this->color . '">$3</span>',
            $segment
        );
    }
}
