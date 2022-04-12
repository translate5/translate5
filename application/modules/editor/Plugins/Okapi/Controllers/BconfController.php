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
 */
class editor_Plugins_Okapi_BconfController extends ZfExtended_RestController
{
    /***
     * Should the data post/put param be decoded to associative array
     * @var bool
     */
    protected bool $decodePutAssociative = true;

    const FILE_UPLOAD_NAME='bconffile';
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
          $this->view->rows = $this->entity->loadAll();
          $this->view->total = $this->entity->getTotalCount();
          if($this->view->total === 0){
              $this->entity->checkSystemBconf();
              $this->view->total = 1;
          }
     }

    public function deleteAction()
    {
        $dir = $this->entity->getDataDirectory($this->getParam('id'));
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
    public function exportbconfAction()
    {
        $okapiName = $this->getParam('okapiName');
        $dir = $this->entity->getDataDirectory($this->getParam('bconfId'));
        $exportFile = "$dir/export.bconf";
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $okapiName . '.bconf');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($exportFile));
        ob_clean();
        flush();
        readfile($exportFile);
        exit;

    }
     private function packBconf(){
         $bconf = new $this->entityClass();
         $bconfId = $this->getParam('bconfId');
         if (!$bconfId) {
             return false;
         }
         return $bconf->packBconf($bconfId);
     }

	/**
	 * Import bconf
	 */
	public function     importbconfAction()
	{
        $upload = new Zend_File_Transfer();
        /** @see \Zend_File_Transfer_Adapter_Abstract::getFileInfo */
        $files = $upload->getFileInfo();
        
        if(empty($files)){
            throw new exception('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact support."
            ]);
        }
        
        $file = $files[self::FILE_UPLOAD_NAME];
        /** @var editor_Plugins_Okapi_Models_Bconf $bconf */
        $bconf = new $this->entityClass();
		//TODO get the file name from UI
        $ret = $bconf->importBconf($file['tmp_name'], $file['name']);
        $id = $ret['id'];
        $dir = $this->entity->getDataDirectory($id);
        move_uploaded_file($file['tmp_name'], "$dir/import.bconf");
        $bconf->packBconf($id);
        echo json_encode($ret);
    }

    public function uploadsrxAction()
	{
        $upload = new Zend_File_Transfer();
        $files = $upload->getFileInfo();
        
        if(empty($files)){
            throw new exception('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact support."
            ]);
        }
        $this->entityLoad();
        $dir = $this->entity->getDataDirectory();
        move_uploaded_file($files['srx']['tmp_name'], "$dir/languages.srx");
        $this->entity->packBconf($this->entity->getId());
	}

    public function downloadsrxAction()
    {
        $this->entityLoad();
        $bconf = $this->entity;
        $dir = $bconf->getDataDirectory();
        $file = "$dir/languages.srx";
        $dlName = $bconf->getId() . '~' . $bconf->getName() . '~languages.srx';
        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="'.$dlName.'"');
        header('Cache-Control: no-store');
        header('Content-Length: '.filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }

    public function cloneAction()
    {
        $this->entityLoad();
        $oldDir = $this->entity->getDataDirectory();
        chdir($oldDir);

        $data = $this->entity->toArray();
        unset($data['id']);

        /** @var editor_Plugins_Okapi_Models_Bconf $clone */
        $clone = new $this->entityClass();
        $data['name'] = $this->getParam('name') ?? 'Copy of '. $data['name'];
        $data['default'] = 0;
        $data['customer_id'] =  $data['customer_id'] ?: $this->getParam('customer_id');
        $clone->init($data);
        $clone->save();
        $newId = $clone->getId();
        $newDir = "../$newId";
        mkdir($newDir);

        $files = glob("*");
        foreach ($files as $file){
            copy($file,"$newDir/$file");
        }

        echo json_encode($clone->toArray());
        exit;
    }

    /**
     * Handles isDefaultForCustomer persisted in customer_meta
     * Exits early if possible
     * @param array|null $fields
     * @param $mode
     * @return void
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @see ZfExtended_RestController::putAction
     * @see ZfExtended_RestController::postAction
     */
    protected function setDataInEntity(array $fields = null, $mode = self::SET_DATA_BLACKLIST){
        if(!isset($this->data['isDefaultForCustomer'])) {
            return parent::setDataInEntity($fields, $mode);
        }
        $customerId = (int) $this->data['isDefaultForCustomer'];
        $customerMeta = new editor_Models_Customer_Meta();
        $bconfId = (int) $this->data['id'];
        if ($customerId) {
            try {
                $customerMeta->loadByCustomerId($customerId);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $customerMeta->init(['customerId' => $customerId]); // new entity
            }
        } else {
            $customerMeta->loadRow('defaultBconfId = ?', $bconfId);
        }
        $newDefault = ($bconfId != $customerMeta->getDefaultBconfId()) ? $bconfId : NULL;
        $customerMeta->setDefaultBconfId($newDefault);

        $customerMeta->save();

        if(count($this->data) > 2){ // more than customerDefault is changed, call parent
            return parent::setDataInEntity($fields, $mode);
        }
    }

}