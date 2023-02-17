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

use MittagQI\ZfExtended\Cors;

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
        $this->view->rows = $this->entity->getGridRows();
        $this->view->total = count($this->view->rows);
        // auto-import of default-bconf: when there are no rows we can assume the feature was just installed and the DB is empty
        // then we automatically add the system default bconf
        if($this->view->total < 1){
            $this->entity->importDefaultWhenNeeded();
            $this->view->rows = $this->entity->getGridRows();
            $this->view->total = count($this->view->rows);
        }
    }

    /**
     * Overwritten to add the custom-extensions
     */
    public function getAction(){
        parent::getAction();
        $this->view->rows->customExtensions = $this->entity->findCustomFilterExtensions(); // needed to match the grids data model
    }

    /**
     * Export bconf
     */
    public function downloadbconfAction() {
        $this->entityLoad();
        // CORS header
        Cors::sendResponseHeader();
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

        $this->view->success = !empty($bconf->getId());
        $this->view->id = $bconf->getId();
    }

    /**
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
        $returnData = $clone->toArray();
        $returnData['customExtensions'] = $clone->findCustomFilterExtensions(); // needed to match the grids data model

        foreach($returnData as $key => $val){
            $this->view->$key = $val;
        }
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
     * Uploads a updated SRX
     * This action has a lot of challenges:
     * - The sent SRX is a (maybe outdated) T5 default SRX and needs to be updated. The naming then will be the default name
     * - the sent name is a real custom srx and we need to validate it & change the name
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function uploadsrxAction() {
        if(empty($_FILES)){
            throw new editor_Plugins_Okapi_Exception('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact the support.",
            ]);
        }
        $this->entityLoad();
        $field = $this->getParam('purpose');
        $segmentation = editor_Plugins_Okapi_Bconf_Segmentation::instance();
        $segmentation->processUpload($this->entity, $field, $_FILES['srx']['tmp_name'], basename($_FILES['srx']['name']));
    }

    /**
     * Sets the non-customer/common default bconf
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function setdefaultAction(){
        $this->entityLoad();
        $this->view->oldId = $this->entity->setAsDefaultBconf();
    }
}