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
        $this->view->total = $this->entity->getTotalCount();
        if($this->entity->importDefaultWhenNeeded($this->view->total)){
            $this->view->total += 1;
        }
        $this->view->rows = $this->entity->loadAll();
    }

    public function deleteAction() {
        $this->entity->deleteDirectory($this->getParam('id'));
        parent::deleteAction();
    }

    /**
     * Export bconf
     */
    public function downloadbconfAction() {
        $okapiName = $this->getParam('okapiName');
        $id = (int)$this->getParam('bconfId'); // directory traversal mitigation
        $downloadFile = $this->entity->getFilePath($id);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $okapiName . '.bconf"');
        header('Cache-Control: no-cache');
        header('Content-Length: ' . filesize($downloadFile));
        header('Content-Length: ' . filesize($downloadFile));
        ob_clean();
        flush();
        readfile($downloadFile);
        exit;
    }

    public function uploadbconfAction() {
        $ret = new stdClass();

        empty($_FILES) && throw new ZfExtended_ErrorCodeException('E1212', [
            'msg' => "No upload files were found. Please try again. If the error persists, please contact support.",
        ]);
        $bconf = new editor_Plugins_Okapi_Models_Bconf($_FILES[self::FILE_UPLOAD_NAME], $this->getAllParams());

        $ret->success = is_object($bconf);
        $ret->id = $bconf->getId();

        echo json_encode($ret);
    }

    /**
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function uploadsrxAction() {
        empty($_FILES) && throw new editor_Plugins_Okapi_Exception('E1212', [
            'msg' => "No upload files were found. Please try again. If the error persists, please contact support.",
        ]);
        $this->entityLoad();
        $bconf = $this->entity;
        $this->getParam('id');

        $srxUploadFile = $_FILES['srx']['tmp_name'];
        $srx = new editor_Utils_Dom();
        $xmlErrors = $srx->load($srxUploadFile) ? $srx->getErrorMsg('', true) : '';

        $rootTag = strtolower($srx->firstChild?->tagName);
        $rootTag !== 'srx' && $xmlErrors .= "\n Invalid root tag '$rootTag'.";

        $xmlErrors && throw new ZfExtended_UnprocessableEntity('E1026',
            ['errors' => [[$xmlErrors]]]
        );

        $srxNameToBe = $bconf->srxNameFor($this->getParam('purpose'));
        move_uploaded_file($srxUploadFile, $bconf->getFilePath(fileName: $srxNameToBe));
        $bconf->file->pack();
    }

    public function downloadsrxAction() {
        $this->entityLoad();
        $bconf = $this->entity;
        $fileName = $bconf->srxNameFor($this->getParam('purpose'));
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
        $clone = new editor_Plugins_Okapi_Models_Bconf(
            ['tmp_name' => $this->entity->getFilePath($this->getParam('id'))],
            $this->getAllParams()
        );

        echo json_encode($clone->toArray());
        exit;
    }

}