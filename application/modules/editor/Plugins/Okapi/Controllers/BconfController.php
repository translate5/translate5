<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * REST Endpoint Controller to serve the Bconf List for the Bconf-Management in the Preferences
 *
 * @property editor_Plugins_Okapi_Bconf_Entity $entity
 */
class editor_Plugins_Okapi_BconfController extends ZfExtended_RestController {
    /***
     * Should the data post/put param be decoded to associative array
     * @var bool
     */
    protected bool $decodePutAssociative = true;

    /**
     * The param-name of the sent bconf
     * @var string
     */
    const FILE_UPLOAD_NAME = 'bconffile';

    /**
     * @var string
     */
    protected $entityClass = 'editor_Plugins_Okapi_Bconf_Entity';

    /**
     * sends all bconfs as JSON
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction() {
        $this->view->rows = $this->entity->loadAll();
        $this->view->total = count($this->view->rows);
        // auto-import of default-bconf: when there are no rows we can assume the feature was just installed and the DB is empty
        // then we automatically add the system default bconf
        if($this->view->total < 1){
            $this->entity->importDefaultWhenNeeded();
            $this->view->rows = $this->entity->loadAll();
            $this->view->total = count($this->view->rows);
        }
    }

    /**
     * Export bconf
     */
    public function downloadbconfAction() {
        $this->entityLoad();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$this->entity->getDownloadFilename());
        header('Cache-Control: no-cache');
        header('Content-Length: ' . filesize($this->entity->getPath()));
        readfile($this->entity->getPath());
        exit;
    }

    public function uploadbconfAction() {
        $ret = new stdClass();

        if(empty($_FILES)){
            throw new ZfExtended_ErrorCodeException('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact the support.",
            ]);
        }
        $postFile = $_FILES[self::FILE_UPLOAD_NAME];
        $name = $this->getParam('name');
        $description = $this->getParam('description');
        $customerId = $this->getParam('customerId');

        if($name == NULL){
            $name = pathinfo($postFile['name'], PATHINFO_FILENAME);
        }
        if($description == NULL){
            $description = '';
        }
        if($customerId != NULL){
            $customerId = intval($customerId);
        }

        $bconf = new editor_Plugins_Okapi_Bconf_Entity();
        $bconf->import($postFile['tmp_name'], $name, $description, $customerId);

        $ret->id = $bconf->getId();
        $ret->success = !empty($ret->id);

        echo json_encode($ret);
    }

    /**
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function downloadsrxAction() {
        $this->entityLoad();
        $srx = $this->entity->getSrx($this->getParam('purpose'));
        $downloadFilename = editor_Utils::filenameFromUserText($this->entity->getName(), false).'-'.$srx->getFile();
        $srx->download($downloadFilename);
        exit;
    }

    /**
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function uploadsrxAction() {
        if(empty($_FILES)){
            throw new editor_Plugins_Okapi_Exception('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact the support.",
            ]);
        }
        $this->entityLoad();
        $srxUploadFile = $_FILES['srx']['tmp_name'];
        $srxUploadName = $_FILES['srx']['name'];
        $srxFilename = $this->entity->getSrxNameFor($this->getParam('purpose'));
        $srxPath = $this->entity->createPath($srxFilename);
        // createan SRX from the upload and validate it
        $srx = new editor_Plugins_Okapi_Bconf_Srx($srxPath, file_get_contents($srxUploadFile));
        if($srx->validate()){
            // in case the uploaded SRX is valid we create a backup of the original we can restore after validating the bconf
            $srxPathBU = $srxPath.'.bu';
            rename($srxPath, $srxPathBU);
            // write the uploaded srx to disk
            $srx->flush();
            // repack the bconf
            $this->entity->getFile()->pack();
            // validate the bconf
            $bconfValidationError = $this->entity->validate();
            if($bconfValidationError !== NULL){
                // restore the original srx & pack the bconf
                unlink($srxPath);
                rename($srxPathBU, $srxPath);
                $this->entity->getFile()->pack();
                throw new editor_Plugins_Okapi_Exception('E1390', ['filename' => $srxUploadName, 'details' => $bconfValidationError]);
            } else {
                // cleanup: remove backup srx
                unlink($srxPathBU);
            }
        } else {
            throw new editor_Plugins_Okapi_Exception('E1390', ['filename' => $srxUploadName, 'details' => $srx->getValidationError()]);
        }
    }

    /**
     * @return void
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Plugins_Okapi_Exception
     */
    public function cloneAction() {
        $this->entityLoad();
        $name = $this->getParam('name');
        $description = $this->getParam('description');
        $customerId = $this->getParam('customerId');
        if($description == NULL){
            $description = '';
        }
        if($customerId != NULL){
            $customerId = intval($customerId);
        }
        $clone = new editor_Plugins_Okapi_Bconf_Entity();
        $clone->import($this->entity->getPath(), $name, $description, $customerId);

        echo json_encode($clone->toArray());
    }

    /**
     * Updates the extensions-mapping.txt file of a Bconf
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function saveextensionsmappingAction(){
        $this->entityLoad();
        $extMap = $this->getRequest()->getRawBody();
        $extensionMapping = $this->entity->getExtensionMapping();
        $extensionMapping->updateByContent($extMap);
    }
}