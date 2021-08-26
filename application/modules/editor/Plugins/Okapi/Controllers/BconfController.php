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
     
     /**
      *
      * @var string
      */
     protected $entityClass = 'editor_Plugins_Okapi_Models_Bconf';
     /**
      * @var editor_Plugins_Okapi_Models_Bconf
      */
     const OKAPI_BCONF_EXPORT_NAME = 'okapi_default_export.bconf';
     const MAXBUFFERSIZE = 1024 * 8;
     const MAXBLOCKLEN = 45000;
     const SIGNATURE = "batchConf";
     const VERSION = 2;
     const  NUMPLUGINS = 0;
     const PIPELINEFILE = "D:/okapi/pipeline.pln";
     
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
     public function postAction()
     {
          $bcongFile = fopen(self::OKAPI_BCONF_EXPORT_NAME, "w") or die("Unable to open file!");
          self::writeUTF(self::SIGNATURE, $bcongFile);
          self::writeInt(self::VERSION, $bcongFile);
          //TODO check the Plugins currentlly not in use
          self::writeInt(self::NUMPLUGINS, $bcongFile);

          //Read the pipeline and extract steps
          self::processPipeline(self::PIPELINEFILE, $bcongFile);
         self::filterConfiguration(3,$bcongFile);
          fclose($bcongFile);
     }
     
     /** Write the UTF-8 value in bconf
      * @param $string
      * @param $bcongFile
      */
     protected function writeUTF($string, $bcongFile)
     {
          $utfString = utf8_encode($string);
          $length = strlen($utfString);
          fwrite($bcongFile, pack("n", $length));
          fwrite($bcongFile, $utfString);
     }
     
     /** Write the Integer value in bconf
      * @param $intValue
      * @param $bcongFile
      */
     protected function writeInt($intValue, $bcongFile)
     {
          fwrite($bcongFile, pack("N", $intValue));
     }
     
     /** Write the Long  value in bcong
      * @param $pipeLine
      * @param $bcongFile
      */
     protected function writeLong($longValue, $bcongFile)
     {
          fwrite($bcongFile, pack("J", $longValue));
     }
     
     protected function processPipeline($pipeLine, $bcongFile)
     {
          $pipeLineFileOpen = fopen($pipeLine, 'r') or die("Unable to open file!");
          $data = fread($pipeLineFileOpen, self::MAXBUFFERSIZE);
          
          $xmlData = new SimpleXMLElement($data);
          $id = 0;
          foreach ($xmlData as $key) {
               $methods = explode("\n", $key);
               foreach ($methods as $method) {
                    if (str_contains($method, 'Path')) {
                     self::harvestReferencedFile($bcongFile,++$id,explode("=", $method)[1]);
                    }
               }
          }
          // Last ID=-1 to mark no more references
          self::writeInt(-1, $bcongFile);
          $fileSize = strlen(preg_replace('/[\n]{0,}/m', '', $data));
          
          $r = ($fileSize % self::MAXBLOCKLEN);
		$n = ($fileSize / self::MAXBLOCKLEN);
		
		$count = $n + (($r > 0) ? 1 : 0);
		self::writeInt($count,$bcongFile); // Number of blocks
          fwrite($bcongFile, $data);
          fclose($pipeLineFileOpen);
     }
     
     protected function harvestReferencedFile($bcongFile, $id, $refPath)
     {
          self::writeInt($id, $bcongFile);
          $path = parse_url($refPath, PHP_URL_PATH);
          
          self::writeUTF(basename($path), $bcongFile);
          
          if ($refPath == '') {
               self::writeLong(0, $bcongFile); // size = 0
               return false;
          }
          //Open the file and read the content
          $file = fopen($refPath, "r") or die("Unable to open file!");
          
          $fileSize = filesize($refPath);
          $fileContent = fread($file, $fileSize);
         
          self::writeLong($fileSize, $bcongFile);
          if ($fileSize > 0) {
               fwrite($bcongFile, $fileContent);
          }
          fclose($file);
     }
     /**
      *
      */
     protected function filterConfiguration($bconfId,$bcongFile){
          $FilterConfiguration= json_encode($this->entity->loadByOkapiId($bconfId));
          $count = 0;
          foreach ($FilterConfiguration as $filter){
               if($filter->custom==1){
                    $count++;
               }
          }
          self::writeInt($count,$bcongFile);
          foreach ($FilterConfiguration as $filter){
               if($filter->custom==1){
                    self::writeUTF($filter->configId);
                    self::writeUTF($filter->configuration);
               }
          }
     }
}