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
class editor_Models_Terminology_TbxObjects_Langset extends editor_Models_Terminology_TbxObjects_Abstract{
    const TABLE_FIELDS = [
        'descrip' => true,
        'transacNote' => true,
        'transacType' => true,
        'collectionId' => false,
        'entryId' => false,
        'termEntryGuid' => false,
        'langSetGuid' => false,
        'guid' => false
    ];
    public int $collectionId = 0;
    public int $entryId = 0;
    public ?string $termEntryGuid = null;
    public ?string $langSetGuid = null;
    public string $language = '';
    public string $languageId = '';
    public string $descrip = '';
    public string $descripType = '';
    public string $descripTarget = '';
    public array $note = [];

    /**
     * @return string
     */
    public function getCollectionKey(): string
    {
        return $this->termEntryGuid.'-'.$this->language;
    }
}
