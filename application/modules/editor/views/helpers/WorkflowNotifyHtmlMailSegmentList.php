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

/**#@+
 * @author Marc Mittag
 * @package portal
 * @version 2.0
 *
 */

use MittagQI\Translate5\Segment\Dto\SegmentView;
use MittagQI\Translate5\Task\Overview\SegmentDataHeader;
use MittagQI\Translate5\Task\Overview\SegmentDataProvider;
use MittagQI\Translate5\Task\Overview\SegmentDataProviderFactory;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\MqmTagFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\ReplaceInternalTagWithSpanFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\TermTagFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\TrackChangesTagFormatter;

/**
 * Formats a Segment List as a HTML table to be send as an E-Mail.
 */
class View_Helper_WorkflowNotifyHtmlMailSegmentList extends Zend_View_Helper_Abstract
{
    protected static $segmentCache = [];

    /**
     * segment list
     */
    protected array $segments;

    private string $segmentHash;

    private SegmentDataProvider $segmentProvider;

    public function __construct()
    {
        $this->segmentProvider = SegmentDataProviderFactory::create()->getProvider([
            new ReplaceInternalTagWithSpanFormatter('title', '#39ffa3'),
            new TermTagFormatter('title'),
            MqmTagFormatter::create('title', '#ff8215'),
            TrackChangesTagFormatter::create(),
        ]);
    }

    /**
     * replace the comment HTML Tags with <br>
     */
    protected function prepareComments(string $comments): string
    {
        $search = ['<span class="author">', '<span class="modified">', '</div>'];
        $replace = ["~#br#~", ' (', ") ~#br#~~#br#~"];
        $comments = str_replace($search, $replace, $comments);

        return str_replace('~#br#~', '<br />', strip_tags($comments));
    }

    /**
     * render the HTML Segment Table
     * @return string
     */
    protected function render()
    {
        //the segments list should not be send to reviewers when the previous workflow step was translations
        if (isset($this->view->triggeringRole) &&
            $this->view->triggeringRole == editor_Workflow_Default::ROLE_TRANSLATOR) {
            return '';
        }

        $t = $this->view->translate; // @phpstan-ignore-line
        if (empty($this->segments)) {
            return '<b>' . $t->_('Es wurden keine Segmente verändert!') . '</b>';
        }

        /* @var $task editor_Models_Task */
        $task = $this->view->task; // @phpstan-ignore-line

        $segments = (function () {
            foreach ($this->segments as $segment) {
                yield new SegmentView($segment);
            }
        })();

        $segmentDataTable = $this->segmentProvider->getSegmentDataTable($task, $segments);

        $result = [];
        $result[] = '<br/>';
        $header = $t->_('Im folgenden die getätigten Änderungen der vorhergehenden Rolle <b>{previousRole}</b>:<br />');
        $header = str_replace('{previousRole}', $t->_($this->view->triggeringStep), $header); // @phpstan-ignore-line

        $result[] = $header;

        $result[] = '<br /><br />';
        $result[] = '<table cellpadding="4">';
        $result[] = '<tr>';

        $th = '<th align="left" valign="top">';

        foreach ($segmentDataTable->header->getFields() as $field) {
            $result[] = $th . $field->label . '</th>';
        }

        $result[] = '</tr>';

        foreach ($segmentDataTable->getRows() as $row) {
            $result[] = "\n" . '<tr>';

            foreach ($segmentDataTable->header->getFields() as $field) {
                if (in_array($field->id, [SegmentDataHeader::FIELD_STATUS, SegmentDataHeader::FIELD_EDIT_STATUS])) {
                    $result[] = '<td valign="top" nowrap="nowrap">' . $row[$field] . '</td>';

                    continue;
                }

                if ($field->id === SegmentDataHeader::FIELD_COMMENTS) {
                    $result[] = '<td valign="top" nowrap="nowrap">' . $this->prepareComments($row[$field] ?? '') . '</td>';

                    continue;
                }

                if ($field->id === SegmentDataHeader::FIELD_MANUAL_QS) {
                    $joinedQualities = ! empty($row[$field]) ? implode(',<br />', $row[$field]) : '';

                    $result[] = '<td valign="top" nowrap="nowrap">' . $joinedQualities . '</td>';

                    continue;
                }

                $result[] = '<td valign="top">' . $row[$field] . '</td>';
            }

            $result[] = '</tr>';
        }

        $result[] = '</table>';
        $result[] = '<br/>';

        return join('', $result);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (empty(self::$segmentCache[$this->segmentHash])) {
            self::$segmentCache[$this->segmentHash] = $this->render();
        }

        return self::$segmentCache[$this->segmentHash];
    }

    /**
     * Helper Initiator
     * @param string|null $segmentHash optional hash to identify the segments to cash them internally
     */
    public function workflowNotifyHtmlMailSegmentList(array $segments, string $segmentHash = null)
    {
        if (empty($segmentHash)) {
            /** @phpstan-ignore-next-line */
            $this->segmentHash = md5(print_r($segments, 1) . $this->view->translate->getTargetLang());
        } else {
            $this->segmentHash = $segmentHash;
        }

        $this->segments = $segments;

        return $this;
    }
}
