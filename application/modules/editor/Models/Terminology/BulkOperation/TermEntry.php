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
class editor_Models_Terminology_BulkOperation_TermEntry extends editor_Models_Terminology_BulkOperation_Abstract
{
    protected editor_Models_Terminology_Models_TermEntryModel|editor_Models_Terminology_Models_Abstract $model;
    protected editor_Models_Terminology_TbxObjects_TermEntry|editor_Models_Terminology_TbxObjects_Abstract $importObject;

    public function __construct() {
        $this->model = new editor_Models_Terminology_Models_TermEntryModel();
        $this->importObject = new editor_Models_Terminology_TbxObjects_TermEntry();
    }

    /**
     * @throws ZfExtended_BadMethodCallException
     */
    protected function fillParentIds(editor_Models_Terminology_TbxObjects_Abstract $elementObject)
    {
        throw new ZfExtended_BadMethodCallException("For term entries this method may not be called");
    }

    public function add(editor_Models_Terminology_TbxObjects_Abstract $importObject)
    {
        $this->importObject = $importObject;
    }

    public function getCurrentEntry(): editor_Models_Terminology_TbxObjects_TermEntry {
        return $this->importObject;
    }
    
    /**
     * Create or update a term entry record in the database, for the current collection and the actual termEntryId
     * (where groupId = termEntryId, where collectionId = termCollectionId)
     *
     * @param bool $mergeTerms
     */
    public function createOrUpdateElement(bool $mergeTerms) //FIXME mergeTerms clean up
    {
        $payload = null;
        $this->mergeTerms = $mergeTerms;

        //find an existing term entry along the termEntryTbxId
        $existing = $this->findExisting($this->importObject, $payload);

        //nothing found, create a new one
        if(is_null($existing)) {
            //create a new entry guid
            $this->importObject->entryGuid = ZfExtended_Utils::uuid();
            $this->model->init([
                'id' => $existing,
                'collectionId' => $this->importObject->collectionId,
                'termEntryTbxId' => $this->importObject->termEntryTbxId,
                'entryGuid' => $this->importObject->entryGuid,
                'descrip' => $this->importObject->descrip
            ]);
            $this->importObject->id = $this->model->save();
            $this->processedCount['created']++;

            //add the newly stored element to the existing list
            $this->processOneExistingRow($this->importObject->id, $this->importObject);
            return;
        }

        //get the hash from the payload
        $hash = array_shift($payload);
        //also get the entryGuid to be reused
        $this->importObject->entryGuid = array_shift($payload);
        $this->importObject->id = $existing;

        if($this->importObject->getDataHash() !== $hash.'#'.$this->importObject->entryGuid) {
            //just update the descrip field, the only field which may change in an termentry
            $this->model->updateImportTbx([[
                'id' => $existing,
                'descrip' => $this->importObject->descrip,
            ]]);
            $this->processedCount['updated']++;
        }
        else {
            $this->processedCount['unchanged']++;
        }
    }
}
