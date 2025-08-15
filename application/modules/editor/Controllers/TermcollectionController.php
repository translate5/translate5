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

use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\LanguageResource\Operation\DeleteLanguageResourceOperation;

/***
 *
 */
class editor_TermcollectionController extends ZfExtended_RestController
{
    public const FILE_UPLOAD_NAME = 'tbxUpload';

    protected $entityClass = 'editor_Models_TermCollection_TermCollection';

    /**
     * @var editor_Models_TermCollection_TermCollection
     */
    protected $entity;

    /**
     * @var array
     */
    protected $uploadErrors = [];

    /**
     * The download-actions need to be csrf unprotected!
     */
    protected array $_unprotectedActions = ['export'];

    public function postAction()
    {
        $this->entity->init();
        $this->decodePutData();
        $this->processClientReferenceVersion();
        $this->setDataInEntity($this->postBlacklist);

        if ($this->validate()) {
            $customerIds = explode(',', $this->data->customerIds ?? $this->getParam('customerIds'));
            $collection = $this->entity->create($this->data->name ?? $this->getParam('name'));

            CustomerAssocService::create()->associateCustomers((int) $collection->getId(), $customerIds);

            $this->entity->setId((int) $collection->getId());
            $this->view->rows = $this->entity->getDataObject();
        }
    }

    /**
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData()
    {
        parent::decodePutData();
        unset($this->data->langResUuid, $this->data->specificId);
    }

    public function deleteAction()
    {
        $this->entityLoad();

        $this->processClientReferenceVersion();

        try {
            DeleteLanguageResourceOperation::create()->delete($this->entity, true);
            $this->view->success = true;
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint) {
            //if there are associated tasks we can not delete the language resource
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1158' => 'A Language Resources cannot be deleted as long as tasks are assigned to this Language Resource.',
            ], 'editor.languageresources');

            throw new ZfExtended_Models_Entity_Conflict('E1158');
        }
    }

    /***
     * Info: function incomplete!
     * Only used in a test at the moment!
     */
    public function exportAction()
    {
        $this->decodePutData();
        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */

        $export = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        /* @var $export editor_Models_Export_Terminology_Tbx */

        $data = $term->loadSortedForExport([$this->data->collectionId]);
        $export->setData($data);
        $exportData = $export->export((bool) $this->getParam('format', false));

        $this->view->filedata = $exportData;
    }

    /***
     * Import the terms into the term collection
     *
     * @throws ZfExtended_Exception
     */
    public function importAction()
    {
        $params = $this->getRequest()->getParams();

        if (! isset($params['collectionId'])) {
            throw new ZfExtended_ValidateException("The import term collection is not defined.");
        }

        $filePath = $this->getUploadedTbxFilePaths();
        if (! $this->validateUpload()) {
            $this->view->success = false;
        } else {
            //the return is needed for the tests
            $this->view->success = $this->entity->importTbx($filePath, $params);
        }
    }

    /***
     * Check if any of the given terms exist in any allowed collection
     * for the logged user.
     */
    public function searchtermexistsAction()
    {
        $params = $this->getRequest()->getParams();
        $searchTerms = json_decode($params['searchTerms']);

        // InstantTranslate supports translation of markup. This is unusable for Terms and thus we need to remove any Markup
        $numTerms = count($searchTerms);
        for ($i = 0; $i < $numTerms; $i++) {
            $searchTerms[$i]->text = strip_tags($searchTerms[$i]->text);
        }
        //get the language root and find all fuzzy languages
        $lang = $params['targetLang'];
        $lang = explode('-', $lang);
        $lang = $lang[0];
        $languages = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages */
        $languages->loadByRfc5646($lang);
        $langs = $languages->getFuzzyLanguages((int) $languages->getId());

        $termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $termCollection editor_Models_TermCollection_TermCollection */
        $collectionIds = $termCollection->getCollectionForAuthenticatedUser();
        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        $this->view->rows = $term->getNonExistingTermsInAnyCollection($searchTerms, $collectionIds, $langs);
    }

    /***
     * This action is only used in a test at the moment!
     */
    public function testgetattributesAction()
    {
        $this->view->rows = $this->entity->getAttributesCountForCollection($this->getParam('collectionId'));
    }

    /***
     * Return the uploaded tbx files paths
     *
     * @throws ZfExtended_FileUploadException
     * @return array
     */
    private function getUploadedTbxFilePaths(): array
    {
        $upload = new Zend_File_Transfer();
        $upload->addValidator('Extension', false, 'tbx');
        // Returns all known internal file information
        $files = $upload->getFileInfo();
        $filePath = [];
        foreach ($files as $file => $info) {
            // file uploaded ?
            if (! $upload->isUploaded($file)) {
                $this->uploadErrors[] = "The file is not uploaded";

                continue;
            }

            // validators are ok ?
            if (! $upload->isValid($file)) {
                $this->uploadErrors[] = "The file:" . $file . " is with invalid file extension";

                continue;
            }

            $filePath[] = $info['tmp_name'];
        }

        return $filePath;
    }

    /**
     * translates and transport upload errors to the frontend
     * @return boolean if there are upload errors false, true otherwise
     */
    protected function validateUpload(): bool
    {
        if (empty($this->uploadErrors)) {
            return true;
        }
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */
        $errors = [
            self::FILE_UPLOAD_NAME => [],
        ];

        foreach ($this->uploadErrors as $error) {
            $errors[self::FILE_UPLOAD_NAME][] = $translate->_($error);
        }

        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        $this->handleValidateException($e);

        return false;
    }
}
