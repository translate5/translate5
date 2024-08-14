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

class TermTagFormatter implements SegmentFormatterInterface
{
    public function __construct(
        private readonly string $messageAttr = 'data-message',
    ) {
    }

    public function __invoke(Task $task, string $segment): string
    {
        //replace term divs by breaking apart to replace the class
        $parts = preg_split('#(<div[^>]+>)#i', $segment, flags: PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $idx => $part) {
            if (! ($idx % 2)) {
                continue;
            }
            $parts[$idx] = $this->modifyTermTag($part);
        }

        return str_ireplace('</div>', '</span>', join('', $parts));
    }

    /**
     * replaces the current term tag with a span tag, containing styles instead css classes
     * In this method the current used term styles are adapted (see main.css)
     *
     * @param string $termTag
     */
    private function modifyTermTag($termTag): string
    {
        $cls = explode(
            ' ',
            preg_replace(
                '#<div[^>]+class="([^"]*)"[^>]*>#i',
                '$1',
                $termTag
            )
        );
        $title = preg_replace('#<div[^>]+title="([^"]*)"[^>]*>#i', '$1', $termTag);

        //adapted css logic:
        if (! empty(array_intersect(['notRecommended', 'supersededTerm', 'deprecatedTerm'], $cls))) {
            return sprintf('<span ' . $this->messageAttr . '="%s" style="border-bottom:none; background-color:#fa51ff;">', $title);
        }

        if (in_array('transNotFound', $cls)) {
            return sprintf('<span ' . $this->messageAttr . '="%s" style="border-bottom-color:#ff0000;">', $title);
        }

        if (in_array('transNotDefined', $cls)) {
            return sprintf('<span ' . $this->messageAttr . '="%s" style="border-bottom-color:#8F4C36;">', $title);
        }

        if (in_array('term', $cls)) {
            return sprintf('<span ' . $this->messageAttr . '="%s" style="background:transparent;border-bottom:1px solid #0000ff;">', $title);
        }

        return '<span>';
    }
}
