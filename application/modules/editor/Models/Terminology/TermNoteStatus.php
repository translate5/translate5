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
    protected array $termNoteMap;

    /**
     * Collected term states not listed in statusMap
     * @var array
     */
    protected array $unknownStates = [];

    /** @var Zend_Config */
    protected Zend_Config $config;

    /**
     * The array have an assignment of the TBX-enabled Term Static that be used in the editor
     * @var array
     */
    protected array $statusMap = [
        'preferredTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_PREFERRED,
        'admittedTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_ADMITTED,
        'legalTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_LEGAL,
        'regulatedTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_REGULATED,
        'standardizedTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_STANDARDIZED,
        'deprecatedTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_DEPRECATED,
        'supersededTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_SUPERSEDED,

        //some more states (uncomplete!), see TRANSLATE-714
        'proposed' => editor_Models_Terminology_TbxObjects_Term::STAT_PREFERRED,
        'deprecated' => editor_Models_Terminology_TbxObjects_Term::STAT_DEPRECATED,
        'admitted' => editor_Models_Terminology_TbxObjects_Term::STAT_ADMITTED,
    ];

    /**
     * editor_Models_Import_TermListParser_TbxFileImport constructor.
     * @throws Zend_Exception
     */
    public function __construct() {
        $this->config = Zend_Registry::get('config');

        //load termNoteMap, and convert all children to arrays
        $this->termNoteMap = array_map(function($item){
            return (array) $item;
        }, $this->config->runtimeOptions->tbx->termImportMap->toArray());
    }

    /**
     * returns the translate5 internal available term status to the one given as termNote in TBX
     * @param array $termNotes
     * @return string
     */
    public function fromTermNotes(array $termNotes) : string
    {
        $foundByPrecedenceType = [];
        /** @var editor_Models_Terminology_TbxObjects_Attribute $termNote */
        foreach ($termNotes as $termNote) {
            $type = $termNote->type;
            //if current termNote is no starttag or type is not allowed to provide a status then we jump out
            if (in_array($termNote->type, array_keys($this->termNoteMap))) {
                $type = 'custom';
            }
            elseif($type != self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS && $type != self::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION) {
                continue;
            }

            //if multiple, then we collect only the first one
            if(isset($foundByPrecedenceType[$type])) {
                continue;
            }

            //collect all results for the different types
            $foundByPrecedenceType[$type] = $this->getStatusFromTermNote($termNote->type, $termNote->value);
        }

        // precedence by $termNote->type: custom states before normative before administrative!
        if(!empty($foundByPrecedenceType['custom'])) {
            return $foundByPrecedenceType['custom'];
        }
        if(!empty($foundByPrecedenceType[self::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION])) {
            return $foundByPrecedenceType[self::DEFAULT_TYPE_NORMATIVE_AUTHORIZATION];
        }
        if(!empty($foundByPrecedenceType[self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS])) {
            return $foundByPrecedenceType[self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS];
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
        // termNote type administrativeStatus are similar to normativeAuthorization,
        // expect that the values have a suffix which must be removed
        if ($termNoteType === self::DEFAULT_TYPE_ADMINISTRATIVE_STATUS) {
            $termNoteValue = str_replace('-admn-sts$', '', $termNoteValue . '$');
        }

        //return mapped status from specific configuration
        if (!empty($this->termNoteMap[$termNoteType]) && !empty($this->termNoteMap[$termNoteType][$termNoteValue])) {
            return $this->termNoteMap[$termNoteType][$termNoteValue];
        }

        //return mapped status from default status map
        if (!empty($this->statusMap[$termNoteValue])) {
            return $this->statusMap[$termNoteValue];
        }

        //collect unknown state
        if (!in_array($termNoteValue, $this->unknownStates)) {
            $this->unknownStates[] = $termNoteValue;
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
}
