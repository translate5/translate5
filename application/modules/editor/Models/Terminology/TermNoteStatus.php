<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * encapsulates the term note status mapping
 */
class editor_Models_Terminology_TermNoteStatus
{
    const DEFAULT_TYPE_ADMINISTRATIVE_STATUS = 'administrativeStatus';
    const DEFAULT_TYPE_NORMATIVE_AUTHORIZATION = 'normativeAuthorization';

    /**
     * Contains the termNote types and values mapped to administrative states:
     * {
     *    "across_userdef_picklist_Verwendung_/_Usage": {
     *        "Verboten / Forbidden": "deprecatedTerm"
     *    }
     * }
     * so that such termNote is mapped to a final administrative state:
     * <termNote type="across_userdef_picklist_Verwendung_/_Usage">Verboten / Forbidden</termNote>
     *
     * @var array
     */
    static protected array $termNoteMap;

    /**
     * Collected term states not listed in statusMap
     * @var array
     */
    protected array $unknownStates = [];

    /** @var Zend_Config */
    protected Zend_Config $config;

    /**
     * editor_Models_Import_TermListParser_TbxFileImport constructor.
     * @throws Zend_Exception
     */
    public function __construct() {
        $this->config = Zend_Registry::get('config');

        //load termNoteMap
        if(empty(self::$termNoteMap)) {
            self::$termNoteMap = [];
            /* @var $db editor_Models_Db_Terminology_TermStatusMap */
            $db = ZfExtended_Factory::get('editor_Models_Db_Terminology_TermStatusMap');
            $loaded = $db->fetchAll()->toArray();
            foreach ($loaded as $statusMap) {
                if(!array_key_exists($statusMap['termNoteType'], self::$termNoteMap)) {
                    self::$termNoteMap[$statusMap['termNoteType']] = [];
                }
                self::$termNoteMap[$statusMap['termNoteType']][$statusMap['termNoteValue']] = $statusMap['mappedStatus'];
            }
        }
    }

    /**
     * returns all termNote types where the termNotes contain a term status relevant value
     * @return array
     */
    public function getAllTypes(): array {
        return array_keys(self::$termNoteMap);
    }

    /**
     * @param editor_Models_Terminology_TbxObjects_Attribute[] $termNotes
     */
    public function fromTermNotesOnImport(array $termNotes, bool &$admnStatFound = false): string {
        $typeAndValueOnly = [];
        foreach($termNotes as $note) {
            if($note->type == 'administrativeStatus') {
                $admnStatFound = true;
            }
            $typeAndValueOnly[] = [
                'type' => $note->type,
                'value' => $note->value
            ];
        }
        return $this->fromTermNotes($typeAndValueOnly);
    }

    /**
     * returns the translate5 internal available term status to the one given as termNote in TBX
     * @param array[] $termNotes
     * @return string
     */
    public function fromTermNotes(array $termNotes) : string
    {
        $foundByPrecedenceType = [];
        foreach ($termNotes as $termNote) {
            $type = $termNote['type'];
            //if current termNote is no starttag or type is not allowed to provide a status then we jump out
            if (!in_array($type, array_keys(self::$termNoteMap))) {
                continue;
            }
            if($type != self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS && $type != self::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION) {
                $type = 'custom';
            }

            //if multiple, then we collect only the first one
            if(isset($foundByPrecedenceType[$type])) {
                continue;
            }

            //collect all results for the different types
            $foundByPrecedenceType[$type] = $this->getStatusFromTermNote($termNote['type'], $termNote['value']);
        }

        // precedence by $termNote->type: normative before administrative before custom states
        if(!empty($foundByPrecedenceType[self::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION])) {
            return $foundByPrecedenceType[self::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION];
        }
        if(!empty($foundByPrecedenceType[self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS])) {
            return $foundByPrecedenceType[self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS];
        }
        if(!empty($foundByPrecedenceType['custom'])) {
            return $foundByPrecedenceType['custom'];
        }

        return $this->config->runtimeOptions->tbx->defaultTermStatus;
    }

    /**
     * returns the mapped status to given termNote type and value, or null if nothing found
     * @param $termNoteType
     * @param $termNoteValue
     * @return string|null
     */
    public function getStatusFromTermNote($termNoteType, $termNoteValue): ?string {
        //return mapped status from specific configuration
        if (!empty(self::$termNoteMap[$termNoteType]) && !empty(self::$termNoteMap[$termNoteType][$termNoteValue])) {
            return self::$termNoteMap[$termNoteType][$termNoteValue];
        }

        //collect unknown state
        $logValue = $termNoteType.': '.$termNoteValue;
        if (!in_array($logValue, $this->unknownStates)) {
            $this->unknownStates[] = $logValue;
        }

        //and return default
        return $this->config->runtimeOptions->tbx->defaultTermStatus;
    }

    /**
     * returns the collected unknown states - if any
     * @return array
     */
    public function getUnknownStates(): array {
        return $this->unknownStates;
    }

    /**
     * Synchronizes the valid term status values into the datatypes picklists
     */
    public function syncStatusToDataTypes() {
        /* @var $db editor_Models_Db_Terminology_TermStatusMap */
        $db = ZfExtended_Factory::get('editor_Models_Db_Terminology_TermStatusMap');
        $db->getAdapter()->query("UPDATE terms_attributes_datatype dt, (
                SELECT termNoteType as type, GROUP_CONCAT(termNoteValue) AS picklistValues
                FROM terms_term_status_map
                GROUP BY termNoteType
            ) as s
        SET dt.picklistValues = s.picklistValues, dt.dataType = 'picklist'
        WHERE dt.label = 'termNote' AND dt.level = 'term' AND dt.type = s.type");
    }

    /**
     * returns the attribute administrativeStatus value from a given term status (returns the first matching), returns the first one too as fallback
     * @param string $termStatus
     * @return string
     */
    public function getAdmnStatusFromTermStatus(string $termStatus): string {
        $result = array_search($termStatus, self::$termNoteMap['administrativeStatus']);
        if($result === false) {
            return reset(self::$termNoteMap['administrativeStatus']);
        }
        return $result;
    }
}
