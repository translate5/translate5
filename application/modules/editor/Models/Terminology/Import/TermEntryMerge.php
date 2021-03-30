<?php

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
        if (isset($entryCollection[$parsedTermEntry->getCollectionId().'-'.$parsedTermEntry->getTermEntryId()])) {
            $exploded = explode('-', $entryCollection[$parsedTermEntry->getCollectionId().'-'.$parsedTermEntry->getTermEntryId()]);
            $id = $exploded[0];
            $descrip = $exploded[1];

            if ($parsedTermEntry->getDescrip() !== $descrip) {
                $this->termEntryModel->updateTermEntryRecord([
                    'id' => $id,
                    'descrip' => $parsedTermEntry->getDescrip(),
                    'entryGuid' => $parsedTermEntry->getEntryGuid()
                ]);
            }
        } else {
             $this->termEntryModel->init([
                'collectionId' =>$parsedTermEntry->getCollectionId(),
                'termEntryId' => $parsedTermEntry->getTermEntryId(),
                'entryGuid' => $parsedTermEntry->getEntryGuid(),
                'descrip' => $parsedTermEntry->getDescrip()
            ]);
            $id = $this->termEntryModel->save();
        }

        return $id;
    }
}
