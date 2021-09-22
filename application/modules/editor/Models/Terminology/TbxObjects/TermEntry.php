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
class editor_Models_Terminology_TbxObjects_TermEntry extends editor_Models_Terminology_TbxObjects_Abstract{
    /**
     * Table field for insert or update.
     * If:
     * 'fieldName' => false -> only insert no check for update attribute
     * 'fieldName' => true -> insert and update
     */
    const TABLE_FIELDS = [
        'termEntryTbxId' => true,
        'entryGuid' => false,
    ];

    const GUID_FIELD = 'entryGuid';

    public int $id = 0;
    public int $collectionId = 0;
    public string $termEntryTbxId = '';
    public ?string $entryGuid = null;
    public array $transacGrp = [];
    public array $ref = [];

    /**
     * @return string
     */
    public function getCollectionKey(): string
    {
        return $this->termEntryTbxId;
    }

    /**
     * return the data hash + the entry guid attached with an #
     * @return string
     */
    public function getDataHash(): string {
        return parent::getDataHash().'#'.$this->entryGuid;
    }
}
