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
class editor_Models_Terminology_Import_TermEntryMerge
{
    /** @var editor_Models_TermCollection_TermCollection */
    protected editor_Models_TermCollection_TermCollection $termCollectionModel;

    /** @var editor_Models_Terminology_Models_TermEntryModel */
    protected editor_Models_Terminology_Models_TermEntryModel $termEntryModel;

    public function __construct() {
        $this->termCollectionModel = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        $this->termEntryModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
    }

    /**
     * Create or update a term entry record in the database, for the current collection and the actual termEntryId
     * (where groupId = termEntryId, where collectionId = termCollectionId)
     *
     * @param editor_Models_Terminology_TbxObjects_TermEntry $parsedTermEntry
     * @param array $entryCollection
     * @return int
     */
    public function createOrUpdateTermEntry(editor_Models_Terminology_TbxObjects_TermEntry $parsedTermEntry, array $entryCollection): int
    {
        // check $parsedTermEntry if entry is for update or to create
        $collectionKey = $parsedTermEntry->getCollectionId().'-'.$parsedTermEntry->getTermEntryTbxId();
        if (isset($entryCollection[$collectionKey])) {
            $exploded = explode('-', $entryCollection[$collectionKey]);
            $id = $exploded[0];
//            $descrip = $exploded[1];

//            if ($parsedTermEntry->getDescrip() !== $descrip) {
//                $this->termEntryModel->updateTermEntryRecord([
//                    'id' => $id,
//                    'descrip' => $parsedTermEntry->getDescrip(),
//                    'entryGuid' => $parsedTermEntry->getEntryGuid()
//                ]);
//            }
        } else {
             $this->termEntryModel->init([
                'collectionId' =>$parsedTermEntry->getCollectionId(),
                'termEntryTbxId' => $parsedTermEntry->getTermEntryTbxId(),
                'entryGuid' => $parsedTermEntry->getEntryGuid(),
                'descrip' => $parsedTermEntry->getDescrip()
            ]);
            $id = $this->termEntryModel->save();
        }

        return $id;
    }
}
