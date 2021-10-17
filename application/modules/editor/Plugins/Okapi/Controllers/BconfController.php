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
class editor_Plugins_Okapi_BconfController extends ZfExtended_RestController
{
    
    const FILE_UPLOAD_NAME='bconffile';
     /**
      *
      * @var string
      */
     protected $entityClass = 'editor_Plugins_Okapi_Models_Bconf';
     /**
      * @var editor_Plugins_Okapi_Models_Bconf
      */
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
     
     /**
      * Export bconf
      */
     public function exportbconfAction()
     {
            $bconf = new editor_Plugins_Okapi_Models_Bconf();
            $okapiName = $this->getParam('okapiName');
            $okapiId = $this->getParam('okapiId');
            if($okapiId ==null || $okapiId ==""){
               return false;
            }
            $bconfFile = $bconf->ExportBconf($okapiName,$okapiId);
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($bconfFile));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: '.filesize($bconfFile));
            ob_clean();
            flush();
            readfile($bconfFile);
            exit;
    
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
        $bconf = new editor_Plugins_Okapi_Bconf_Import();
		//TODO get the file name from UI
  
		$bconf->importBconf($file);
	}
     
}