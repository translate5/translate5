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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfInvalidException;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Helpers\ResourceFileImport;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Pipeline;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use MittagQI\ZfExtended\Controller\Response\Header;

/**
 * REST Endpoint Controller to serve the Bconf List for the Bconf-Management in the Preferences
 *
 * @property BconfEntity $entity
 */
class editor_Plugins_Okapi_BconfController extends ZfExtended_RestController
{
    /**
     * The param-name of the sent bconf
     * @var string
     */
    public const FILE_UPLOAD_NAME = 'bconffile';

    /**
     * Should the data post/put param be decoded to associative array
     */
    protected bool $decodePutAssociative = true;

    /**
     * @var string
     */
    protected $entityClass = BconfEntity::class;

    /**
     * The download-actions need to be csrf unprotected!
     */
    protected array $_unprotectedActions = ['downloadbconf', 'downloadsrx', 'downloadpipeline'];

    /**
     * sends all bconfs as JSON
     *
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function indexAction(): void
    {
        $this->view->rows = $this->entity->getGridRows();
        $this->view->total = count($this->view->rows);
        // auto-import of default-bconf: when there are no rows we can assume the feature was just installed and the DB is empty
        // then we automatically add the system default bconf
        if ($this->view->total < 1) {
            $this->entity->importDefaultWhenNeeded();
            $this->view->rows = $this->entity->getGridRows();
            $this->view->total = count($this->view->rows);
        }
    }

    /**
     * Overwritten to add the custom-extensions
     *
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function getAction(): void
    {
        parent::getAction();
        $this->view->rows->customExtensions = $this->entity->findCustomFilterExtensions(); // needed to match the grids data model
    }

    /**
     * Export bconf
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    public function downloadbconfAction(): void
    {
        $this->entityLoadAndRepack();
        Header::sendDownload(
            $this->entity->getDownloadFilename(),
            'application/octet-stream',
            'no-cache',
            filesize($this->entity->getPath())
        );
        readfile($this->entity->getPath());
        exit;
    }

    /**
     * @throws BconfInvalidException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function uploadbconfAction(): void
    {
        if (empty($_FILES)) {
            throw new ZfExtended_ErrorCodeException('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact the support.",
            ]);
        }
        $postFile = $_FILES[self::FILE_UPLOAD_NAME];
        $name = $this->getParam('name');
        $description = $this->getParam('description');
        $customerId = $this->getParam('customerId');
        if ($name == null) {
            $name = pathinfo($postFile['name'], PATHINFO_FILENAME);
        }
        if ($description == null) {
            $description = '';
        }
        if ($customerId != null) {
            $customerId = intval($customerId);
        }
        $bconf = new BconfEntity();
        $bconf->import($postFile['tmp_name'], $name, $description, $customerId);

        $this->view->success = ! empty($bconf->getId());
        $this->view->id = $bconf->getId();
    }

    /**
     * @throws BconfInvalidException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function cloneAction(): void
    {
        $this->entityLoadAndRepack();
        $name = $this->getParam('name');
        $description = $this->getParam('description');
        $customerId = $this->getParam('customerId');
        if ($description == null) {
            $description = '';
        }
        if ($customerId != null) {
            $customerId = intval($customerId);
        }
        $clone = new BconfEntity();
        $clone->import($this->entity->getPath(), $name, $description, $customerId);
        $returnData = $clone->toArray();
        $returnData['customExtensions'] = $clone->findCustomFilterExtensions(); // needed to match the grids data model

        foreach ($returnData as $key => $val) {
            $this->view->$key = $val;
        }
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    public function downloadsrxAction(): void
    {
        $this->entityLoadAndRepack();
        $srx = $this->entity->getSrx($this->getParam('purpose'));
        $downloadFilename = editor_Utils::filenameFromUserText($this->entity->getName(), false) . '-' . $srx->getFile();
        $srx->download($downloadFilename);
        exit;
    }

    /**
     * Uploads a updated SRX
     * This action has a lot of challenges:
     * - The sent SRX is a (maybe outdated) T5 default SRX and needs to be updated. The naming then will be the default name
     * - the sent name is a real custom srx and we need to validate it & change the name
     *
     * @throws OkapiException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_UnprocessableEntity
     * @throws \MittagQI\ZfExtended\MismatchException
     */
    public function uploadsrxAction(): void
    {
        if (empty($_FILES)) {
            throw new OkapiException('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact the support.",
            ]);
        }
        $this->entityLoadAndRepack();
        $field = $this->getParam('purpose');
        $segmentation = Segmentation::instance();
        $segmentation->processUpload($this->entity, $field, $_FILES['srx']['tmp_name'], basename($_FILES['srx']['name']));
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    public function downloadpipelineAction(): void
    {
        $this->entityLoadAndRepack();
        $pipeline = $this->entity->getPipeline();
        $downloadFilename = editor_Utils::filenameFromUserText($this->entity->getName(), false) . '-' . $pipeline->getFile();
        $pipeline->download($downloadFilename);
        exit;
    }

    /**
     * @throws OkapiException
     * @throws ZfExtended_Exception
     * @throws Throwable
     */
    public function uploadpipelineAction(): void
    {
        if (empty($_FILES)) {
            throw new OkapiException('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact the support.",
            ]);
        }
        $this->entityLoadAndRepack();
        $pipeline = new Pipeline($this->entity->getPipelinePath(), file_get_contents($_FILES['pln']['tmp_name']));
        $errMsg = ResourceFileImport::addToBConf($pipeline, $this->entity);
        if ($errMsg) {
            throw new OkapiException('E1687', [
                'details' => $errMsg,
            ]);
        }
    }

    /**
     * Sets the non-customer/common default bconf
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function setdefaultAction(): void
    {
        $this->entityLoad();
        $this->view->oldId = $this->entity->setAsDefaultBconf();
    }

    /**
     * Helper to check if a Bconf supports a specific extension
     *
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function filetypesupportAction(): void
    {
        $this->entityLoad();
        $extension = $this->getParam('extension');
        $this->view->success = false;
        if (! empty($extension) && $this->entity->getExtensionMapping()->hasExtension(strtolower($extension))) {
            $this->view->success = true;
            $this->view->extension = strtolower($extension);
        }
    }

    /**
     * Helper to load the entity and repack it if the bconf is outdated
     * This is needed to avoid outdated stuff leaving the system or being cloned
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    private function entityLoadAndRepack(): void
    {
        $this->entityLoad();
        $this->entity->repackIfOutdated();
    }
}
