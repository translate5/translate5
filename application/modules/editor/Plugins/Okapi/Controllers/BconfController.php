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
        $dir = $this->entity->getDataDirectory($this->getParam('okapiId'));
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
         $bconf = new editor_Plugins_Okapi_Models_Bconf();
         $okapiId = $this->getParam('okapiId');
         if ($okapiId == null || $okapiId == "") {
             return false;
         }
         $bconf->packBconf($okapiId);
     }
	
	/**
	 * Import bconf
	 */
	public function importbconfAction()
	{
        $upload = new Zend_File_Transfer();
        $files = $upload->getFileInfo();
        
        if(empty($files)){
            throw new exception('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact support."
            ]);
        }
        
        $file = [];
        if(array_key_exists('0', $files)){
            $file = $files[0];
        }else{
            $file = $files[self::FILE_UPLOAD_NAME];
        }
        
        $bconf = new editor_Plugins_Okapi_Models_Bconf();
		//TODO get the file name from UI
        $ret = $bconf->importBconf($file);
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
        $oldId = $this->getParam('id');
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
     * @param Zend_EventManager_Event $e
     * @return void
     */
    public static function handleCustomerDefaultBconf($e){
        /** @var array $data */
        $data = $e->getParam('data');
        if(isset($data['isDefaultForCustomer'])){
            /** @var editor_Models_Customer_Meta $customerMeta */
            $customerMeta = new editor_Models_Customer_Meta();
            $customerId = (int) $data['isDefaultForCustomer'];
            $bconfId = (int) $data['id'];
            if ($customerId) {
                try {
                    $customerMeta->loadByCustomerId($customerId);
                } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                    $customerMeta->init(['customerId' => $customerId]);
                } // new entity
            } else {
                $customerMeta->loadRow('defaultBconfId = ?', $bconfId);
            }

            $currentCustomerDefault = $customerMeta->getDefaultBconfId();
            $newCustomerDefault = $bconfId;
            if($newCustomerDefault != $currentCustomerDefault){
                $customerMeta->setDefaultBconfId($newCustomerDefault);
            } else { // bconf was DEselected as customer default
                $customerMeta->setDefaultBconfId(NULL);
            }

            $customerMeta->save();

            if(count($data)===2){ // only customerDefault is changed, delete data to skip further proceeding

            }

        }
    }
     
}
class_alias('editor_Plugins_Okapi_BconfController', 'editor_Plugins_Okapi_Controllers_BconfController'); // needed for eventmanager callback
