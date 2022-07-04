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
 *
 * REST Endpoint Controller to serve the Bconf List for the Bconf-Management in the Preferences
 *
 */

/**
 * @property editor_Plugins_Okapi_Models_Bconf $entity
 */
class editor_Plugins_Okapi_BconfController extends ZfExtended_RestController {
    /***
     * Should the data post/put param be decoded to associative array
     * @var bool
     */
    protected bool $decodePutAssociative = true;

    const FILE_UPLOAD_NAME = 'bconffile';
    /**
     * @var string
     */
    protected $entityClass = 'editor_Plugins_Okapi_Models_Bconf';

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
        ob_clean();
        flush();
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

        $bconf = new editor_Plugins_Okapi_Models_Bconf();
        $bconf->import($postFile['tmp_name'], $name, $description, $customerId);

        $ret->id = $bconf->getId();
        $ret->success = !empty($ret->id);

        echo json_encode($ret);
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
        $bconf = $this->entity;
        $srxUploadFile = $_FILES['srx']['tmp_name'];
        $srx = new editor_Utils_Dom();
        $xmlErrors = '';
        if($srx->load($srxUploadFile)){
            $rootTag = strtolower($srx->firstChild?->tagName);
            if($rootTag !== 'srx'){
                $xmlErrors .= "\nInvalid root tag '$rootTag'.";
            }
        } else {
            $xmlErrors .= "\n".$srx->getErrorMsg('', true);
        }


        if(!empty($xmlErrors)){
            throw new editor_Plugins_Okapi_Exception('E1390', ['details' => $xmlErrors]);
        }

        $srxNameToBe = $bconf->getSrxNameFor($this->getParam('purpose'));
        move_uploaded_file($srxUploadFile, $bconf->createPath(fileName: $srxNameToBe));
        $bconf->getFile()->pack();
    }

    public function downloadsrxAction() {
        $this->entityLoad();
        $bconf = $this->entity;
        $fileName = $bconf->getSrxNameFor($this->getParam('purpose'));
        $file = $bconf->getFilePath(fileName: $fileName);

        $dlName = $bconf->getName() . '-' . $fileName;
        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="' . $dlName . '"');
        header('Cache-Control: no-store');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
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
        // TODO BCONF: rework
        $clone = new editor_Plugins_Okapi_Models_Bconf(
            ['tmp_name' => $this->entity->getFilePath($this->getParam('id'))],
            $this->getAllParams()
        );

        echo json_encode($clone->toArray());
        exit;
    }

}