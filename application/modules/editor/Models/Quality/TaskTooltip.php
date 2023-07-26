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

/**
 * Provides the Markup for the Quality Tooltip of the Task Grid, a short summary of our qualities
 */
class editor_Models_Quality_TaskTooltip extends editor_Models_Quality_AbstractView
{
    
    /**
     * hexadecimal representation of the character to use for an incomplete quality
     * @var string
     */
    private const HEX_INCOMPLETE = '"\uF071"';
    /**
     * hexadecimal representation of the character to use for an faulty quality
     * @var string
     */
    private const HEX_FAULTY = '"\uF057"';
    /**
     * Forces only not false positive qualities to be fetched
     * @var integer
     */
    protected $falsePositiveRestriction = 0;

    protected $hasNumFalsePositives = true;
    
    public function getMarkup() : string
    {
        $html = '<table class=""><tbody>';
        $hasIncomplete = false;
        $addFaultsComment = false;
        $addCriticalErrorsComment = false;

        foreach ($this->rows as $row) {
            $hasCriticalErrors = $row->mustBeZeroErrors && $row->qcount > $row->qcountfp;
            $addCriticalErrorsComment = $addCriticalErrorsComment || $hasCriticalErrors;

            $html .=
                '<tr>'
                . '<td>' . $row->text . ':</td>'
                . '<td>' . $row->qcount . '</td>'
                . '<td>';

            if (property_exists($row, 'qcomplete') && !$row->qcomplete) {
                $html .= ' ' . $this->getStatusSymbol('incomplete');
                $hasIncomplete = true;
            }

            $hasFaults = property_exists($row, 'qfaulty') && $row->qfaulty;
            $addFaultsComment = $addFaultsComment || $hasFaults;

            if ($hasFaults || $hasCriticalErrors) {
                $html .= ' ' . $this->getStatusSymbol('error');
            }

            $html .= '</td></tr>';
        }

        if ($hasIncomplete || $addFaultsComment || $addCriticalErrorsComment) {
            $html .= '<tr><td colspan="3">';
            if ($hasIncomplete) {
                $html .=
                    '<br>'
                    . $this->getStatusSymbol('incomplete')
                    . ' '
                    . $this->translate->_('Diese Kategorie wurde nicht oder nur unvollständig geprüft');
            }
            if ($addFaultsComment) {
                $html .=
                    '<br>'
                    . $this->getStatusSymbol('error')
                    . ' '
                    . $this->translate->_(
                        'Es gibt Interne Tag Fehler die einen fehlerfreien Export der Aufgabe verhindern'
                    );
            }
            if ($addCriticalErrorsComment) {
                $html .=
                    '<br>'
                    . $this->getStatusSymbol('error')
                    . ' '
                    . $this->translate->_(editor_Segment_Quality_Provider::CATEGORY_CRITICAL_ERROR_TOOLTIP);
            }
            $html .= '</td></tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    private function getStatusSymbol(string $type) : string
    {
        switch ($type) {
            case 'incomplete':
                return '<span class="x-grid-symbol t5-quality-incomplete">'.json_decode(self::HEX_INCOMPLETE).'</span>';

            case 'error':
                return '<span class="x-grid-symbol t5-quality-faulty">'.json_decode(self::HEX_FAULTY).'</span>';

            default:
                return '';
        }
    }
}
