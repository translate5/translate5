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
class editor_Models_Terminology_TbxObjects_TransacGrp extends editor_Models_Terminology_TbxObjects_Abstract{
    /**
     * Table field for insert or update.
     * If:
     * 'fieldName' => false -> only insert no check for update attribute
     * 'fieldName' => true -> insert and update
     */
    const TABLE_FIELDS = [
        'transac' => true,
        'date' => true,
        'language' => true,
        'attrLang' => false,
        'transacNote' => true,
        'transacType' => true,
        'isDescripGrp' => true,
        'collectionId' => false,
        'termEntryId' => false,
        'termId' => false,
        'termTbxId' => true,
        'target' => true,
        'termGuid' => false,
        'termEntryGuid' => true,
        'langSetGuid' => true,
        'guid' => false,
        'elementName' => true
    ];
    public int $collectionId = 0;

    public int $termEntryId = 0;
    public ?int $termId = null;
    public ?string $termTbxId = null;
    public ?string $termGuid = null;
    public ?string $termEntryGuid = null;
    public ?string $langSetGuid = null;
    public ?string $guid = null;
    public string $elementName = '';
    public ?string $language = null;
    public string $attrLang = '';
    public ?string $target = null;
    public string $transac = '';
    public string $date = '';
    public string $transacNote = '';
    public string $transacType = '';
    public int $isDescripGrp = 0;

    /**
     * @return string
     */
    public function getCollectionKey(): string
    {
        return $this->elementName . '-' . $this->transac . '-' . $this->isDescripGrp . '-' . $this->termEntryGuid. '-' . $this->language.'-'.$this->termTbxId;
    }
}
