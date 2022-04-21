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

/**
 *
 * REST Endpoint Controller to serve the Bconf List for the Bconf-Management in the Preferences
 *
 */

/**
 * @property editor_Plugins_Okapi_Models_Bconf $entity
 * @method editor_Plugins_Okapi_Models_Bconf entityLoad()
 */
class editor_Plugins_Okapi_BconfController extends ZfExtended_RestController
{
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
     public function indexAction()
     {
          $this->view->total = $this->entity->getTotalCount();
          if($this->view->total === 0){
              $this->entity->importDefaultWhenNeeded(true);
              $this->view->total = 1;
          }
          $this->view->rows = $this->entity->loadAll();
     }

    public function deleteAction()
    {
        $idToDelete = $this->getParam('id');
        $systemBconf_row = $this->entity->loadRow('name = ?', $this->entity::SYSTEM_BCONF_NAME);
        if($idToDelete == $systemBconf_row['id']){
            throw new ZfExtended_NoAccessException();
        }

        $dir = $this->entity->getDataDirectory($idToDelete);
        parent::deleteAction();
        if (preg_match('/\d+$/', $dir)) { // just to be safe
            /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
            $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
            );
            $recursivedircleaner->delete($dir);
        }
    }
     
     /**
      * Export bconf
      */
    public function downloadbconfAction()
    {
        $okapiName = $this->getParam('okapiName');
        $id = (int) $this->getParam('bconfId'); // file traversal mitigation
        $downloadFile = $this->entity->getFilePath($id);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $okapiName . '.bconf');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($downloadFile));
        ob_clean();
        flush();
        readfile($downloadFile);
        exit;

    }

	public function uploadbconfAction()
	{
        $bconf = NULL;
        $ret = new stdClass();

        try {
            empty($_FILES) && throw new ZfExtended_ErrorCodeException('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact support."
            ]);
            $bconf = new editor_Plugins_Okapi_Models_Bconf($_FILES[self::FILE_UPLOAD_NAME], $this->getAllParams());
        } catch(editor_Plugins_Okapi_Exception $e){
            //TODO add excpetion message to $ret
        }

        $ret->success = is_object($bconf);
        $ret->id      = $bconf?->getId();

        echo json_encode($ret);
    }

    /**
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function uploadsrxAction()
	{
        empty($_FILES) && throw new editor_Plugins_Okapi_Exception('E1212', [
            'msg' => "No upload files were found. Please try again. If the error persists, please contact support."
        ]);
        $filePath = $_FILES['srx']['tmp_name'];
        $errorDetails = '';
        libxml_use_internal_errors(use_errors: true);
        $errorType = [LIBXML_ERR_WARNING => 'warning', LIBXML_ERR_ERROR => 'error', LIBXML_ERR_FATAL => 'fatal'];

        $xml = simplexml_load_file($filePath);
        $xmlErrors = libxml_get_errors();
        if($xmlErrors){
            $messages = '';
            foreach ($xmlErrors as /** @var LibXMLError $err */ $err){
                $messages .= "{$errorType[$err->level]}@$err->line,$err->column: $err->message\n";
            }
            $messages && $errorDetails = "Error".(count($xmlErrors)>1 ? 's' : '').": <pre>$messages</pre>";
        }
        $rootTag = $xml ? $xml->getName() : 'srx';
        strtolower($rootTag) !== 'srx' && $errorDetails .= " Invalid root tag '$rootTag'.";
        $errorDetails && throw new ZfExtended_UnprocessableEntity('E1026', ['errors' => [[$messages]]]);

        $bconf = $this->entityLoad();
        move_uploaded_file($filePath, "{$bconf->getDataDirectory()}/languages.srx");
        $bconf->file->pack();
	}

    public function downloadsrxAction()
    {
        $bconf = $this->entityLoad();
        $file = "{$bconf->getDataDirectory()}/languages.srx";
        $dlName = $bconf->getName() . '-languages.srx';
        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="'.$dlName.'"');
        header('Cache-Control: no-store');
        header('Content-Length: '.filesize($file));
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
    public function cloneAction()
    {
        $clone = new editor_Plugins_Okapi_Models_Bconf(
            ['tmp_name' => $this->entity->getFilePath($this->getParam('id'))],
            $this->getAllParams()
        );

        echo json_encode($clone->toArray());
        exit;
    }

    /**
     * Persists isDefaultForCustomer to customer_meta
     * @param array|null $fields
     * @param bool $mode
     * @throws Zend_Db_Statement_Exception|ZfExtended_Models_Entity_Exceptions_IntegrityConstraint|ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey|ZfExtended_Models_Entity_NotFoundException
     * @see ZfExtended_RestController::putAction, ZfExtended_RestController::postAction
     */
    protected function setDataInEntity(array $fields = null, $mode = self::SET_DATA_BLACKLIST){
        if(isset($this->data['isDefaultForCustomer'])) {
            $bconfId    = (int)$this->data['id'];
            $customerId = (int)$this->data['isDefaultForCustomer'];

            $customerMeta = new editor_Models_Customer_Meta();
            if(!$customerId) {
                $customerMeta->loadRow('defaultBconfId = ?', $bconfId);
            } else try {
                $customerMeta->loadByCustomerId($customerId);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $customerMeta->init(['customerId' => $customerId]); // new entity
            }

            $newDefault = ($bconfId != $customerMeta->getDefaultBconfId()) ? $bconfId : NULL;
            $customerMeta->setDefaultBconfId($newDefault);

            $customerMeta->save();
        }
        if($this->data && count($this->data) > 2 || !isset($this->data['isDefaultForCustomer'])){ // more than customerDefault is changed, call parent
            parent::setDataInEntity($fields, $mode);
        }
    }

}