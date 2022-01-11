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
class editor_Models_Terminology_TbxObjects_Term extends editor_Models_Terminology_TbxObjects_Abstract{
    /**
     * Table field for insert or update.
     * If:
     * 'fieldName' => false -> only insert no check for update attribute
     * 'fieldName' => true -> insert and update
     */
    const TABLE_FIELDS = [
        'updatedBy' => true,
        'updatedAt' => false, // is updated automatically in DB
        'collectionId' => false,
        'termEntryId' => false,
        'languageId' => false,
        'language' => false,
        'term' => true,
        // 'proposal' => ?
        'status' => true,
        'processStatus' => true,
        'definition' => true,
        'termEntryTbxId' => false,
        'termTbxId' => false,
        'termEntryGuid' => false,
        'langSetGuid' => false,
        'guid' => false,
        'tbxCreatedBy' => true,
        'tbxCreatedAt' => true, //see special hash treatment below!
        'tbxUpdatedBy' => true,
        'tbxUpdatedAt' => true, //see special hash treatment below!
    ];

    const TERM_DEFINITION = 'definition';
    const TERM_STANDARD_PROCESS_STATUS= 'finalized';

    const STAT_PREFERRED = 'preferredTerm';
    const STAT_ADMITTED = 'admittedTerm';
    const STAT_LEGAL = 'legalTerm';
    const STAT_REGULATED = 'regulatedTerm';
    const STAT_STANDARDIZED = 'standardizedTerm';
    const STAT_DEPRECATED = 'deprecatedTerm';
    const STAT_SUPERSEDED = 'supersededTerm';

    const STAT_NOT_FOUND = 'STAT_NOT_FOUND'; //Dieser Status ist nicht im Konzept definiert, sondern wird nur intern verwendet!
    const TRANSSTAT_FOUND = 'transFound';
    const TRANSSTAT_NOT_FOUND = 'transNotFound';
    const TRANSSTAT_NOT_DEFINED ='transNotDefined';
    const CSS_TERM_IDENTIFIER = 'term';

    public ?int $id = null;
    public int $updatedBy = 0;
    public string $updatedAt = '';
    public int $collectionId = 0;
    public int $termEntryId = 0;
    public int $languageId = 0;
    public string $language = '';
    public string $term = '';
    // public string $proposal = '';
    public string $status = '';
    public string $processStatus = '';
    public string $definition = '';
    public string $termEntryTbxId = '';
    public string $termTbxId = '';
    public ?string $termEntryGuid = null;
    public ?string $langSetGuid = null;
    public ?string $guid = null;

    public ?int $tbxCreatedBy = null;
    public string $tbxCreatedAt = ''; //see special hash treatment below!
    public ?int $tbxUpdatedBy = null;
    public string $tbxUpdatedAt = ''; //see special hash treatment below!

    public string $descrip = '';
    public string $descripType = '';
    public string $descripTarget = '';
    public array $admin = [];
    public array $xref = [];
    public array $ref = [];
    public array $transacNote = [];
    public array $termNote = [];
    public array $note = [];

    /**
     * @return string
     */
    public function getCollectionKey(): string
    {
        return $this->termEntryTbxId.'-'.$this->language.'-'.$this->termTbxId;
    }

    protected function makeDataForHash(): array
    {
        $data = parent::makeDataForHash();
        foreach(['tbxUpdatedAt', 'tbxCreatedAt'] as $dateItem) {
            if($data[$dateItem] === '0000-00-00 00:00:00') {
                $data[$dateItem] = '';
            }
        }
        return $data;
    }
}
