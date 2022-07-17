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
        $srxUploadFile = $_FILES['srx']['tmp_name'];
        $srxUploadName = $_FILES['srx']['name'];
        $field = $this->getParam('purpose');
        $otherField = ($field == 'source') ? 'target' : 'source';
        $srx = $this->entity->getSrx($field);
        $otherSrx = $this->entity->getSrx($otherField);
        $segmentation = editor_Plugins_Okapi_Bconf_Segmentation::instance();
        $pipeline = $this->entity->getPipeline();
        $content = $this->entity->getContent();
        // set the srx-content from the upload and validate it
        $srx->setContent(file_get_contents($srxUploadFile));
        if($srx->validate()){
            // if the SRX is a translate5 default SRX, we need no further validation
            if($segmentation->isDefaultSrx($srx)){ // the isDefaultSrx call will update the content to the current revision if it is a default SRX
                // if both srx's are identical, we copy the name/path over
                if($srx->getHash() === $otherSrx->getHash()){
                    $srx->setPath($otherSrx->getPath());
                } else {
                    $srx->setPath($this->entity->createPath('languages-'.$field));
                    if($otherSrx->getPath() === $srx->getPath()){ // the almost impossible case: the target srx is called "languages-source" (or vice versa)
                        $otherSrx->setPath($this->entity->createPath('languages-'.$otherField));
                        $otherSrx->flush();
                    }
                }
                $srx->flush();
                $this->updateSrxInFiles($pipeline, $content, $field, $srx, $otherField, $otherSrx);
            } else {
                // real custom SRX uploads must be validated with OKAPI
                $customFile = editor_Plugins_Okapi_Bconf_Segmentation::createCustomFile($srxUploadName, $field, $otherField);
                $srx->setPath($this->entity->createPath($customFile));
                if($otherSrx->getPath() === $srx->getPath()){ // another almost impossible case: custom name equals the other srx
                    $customFile .= strval(rand(0, 9)); // so we put not much effort into this ...
                    $srx->setPath($this->entity->createPath($customFile));
                }
                // create backups
                rename($srx->getPath(), $srx->getPath().'.bu');
                rename($pipeline->getPath(), $pipeline->getPath().'.bu');
                rename($content->getPath(), $content->getPath().'.bu');
                // write the uploaded srx to disk
                $srx->flush();
                // update the dependencies to disk
                $this->updateSrxInFiles($pipeline, $content, $field, $srx, $otherField, $otherSrx);
                // pack the bconf
                $this->entity->pack();
                // validate the bconf by testing it with okapi
                $bconfValidationError = $this->entity->validate();
                if($bconfValidationError !== NULL){
                    // restore the original srx & pack the bconf
                    unlink($srx->getPath());
                    rename($srx->getPath().'.bu', $srx->getPath());
                    unlink($pipeline->getPath());
                    rename($pipeline->getPath().'.bu', $pipeline->getPath());
                    unlink($content->getPath());
                    rename($content->getPath().'.bu', $content->getPath());
                    $this->entity->pack();
                    $this->entity->invalidateCaches(); // invalidate the cached files, we changed the underlying files ...
                    throw new editor_Plugins_Okapi_Exception('E1390', ['filename' => $srxUploadName, 'details' => $bconfValidationError]);
                } else {
                    // cleanup: remove backup files
                    unlink($srx->getPath().'.bu');
                    unlink($pipeline->getPath().'.bu');
                    unlink($content->getPath().'.bu');
                }
            }
        } else {
            throw new editor_Plugins_Okapi_Exception('E1390', ['filename' => $srxUploadName, 'details' => $srx->getValidationError()]);
        }
    }

    private function updateSrxInFiles(editor_Plugins_Okapi_Bconf_Pipeline $pipeline, editor_Plugins_Okapi_Bconf_Content $content,
                                      string $field, editor_Plugins_Okapi_Bconf_Segmentation_Srx $srx,
                                      string $otherField, editor_Plugins_Okapi_Bconf_Segmentation_Srx $otherSrx){
        $pipeline->setSrxFile($field, $srx->getFile());
        $pipeline->setSrxFile($otherField, $otherSrx->getFile());
        $pipeline->flush();
        $content->setSrxFile($field, $srx->getFile());
        $content->setSrxFile($otherField, $otherSrx->getFile());
        $content->flush();
    }
}