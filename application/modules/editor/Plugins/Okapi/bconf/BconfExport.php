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
 * Generate new bconf file
 *
 */
class editor_Plugins_Okapi_Bconf_BconfExport extends editor_Plugins_Okapi_Bconf_BconfUtil
{
     const OKAPI_BCONF_BASE_PATH = 'D:/okapi/';
     const MAXBUFFERSIZE = 1024 * 8;
     const MAXBLOCKLEN = 45000;
     const SIGNATURE = "batchConf";
     const VERSION = 2;
     const  NUMPLUGINS = 0;
     const PIPELINEFILE = "D:/okapi/pipeline.pln";
     const EXTFILESDIR="G:/projects/Marc/projects/translate5/application/modules/editor/Plugins/Okapi/data/okapi-import-bconf-generation/testfiles";
    
     /**
      * Export bconf
      */
     public function ExportBconf($okapiName,$okapiId)
     {
          $fileExist = file_exists(self::OKAPI_BCONF_BASE_PATH.$okapiName.'.bconf');
          if($fileExist){
               //handle if file already exist
          }
          $bcongFile = fopen(self::OKAPI_BCONF_BASE_PATH.$okapiName.'.bconf', "w") or die("Unable to open file!");
          $this->writeUTF(self::SIGNATURE, $bcongFile);
          $this->writeInt(self::VERSION, $bcongFile);
          //TODO check the Plugins currentlly not in use
          $this->writeInt(self::NUMPLUGINS, $bcongFile);

          //Read the pipeline and extract steps
          $this->processPipeline(self::PIPELINEFILE, $bcongFile);
          $this->filterConfiguration($okapiId,$bcongFile);
          $this->extensionsMapping($okapiId,$bcongFile);
          fclose($bcongFile);
     }
     
     protected function processPipeline($pipeLine, $bcongFile)
     {
          $pipeLineFileOpen = fopen($pipeLine, 'r') or die("Unable to open file!");
          $data = fread($pipeLineFileOpen, self::MAXBUFFERSIZE);
          //force to add new line
          
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
          ++$fileSize;
          $r = (int)($fileSize % self::MAXBLOCKLEN);
          $n = (int)($fileSize / self::MAXBLOCKLEN);
          // Number of blocks
          $count = $n + (($r > 0) ? 1 : 0);
          self::writeInt($count,$bcongFile);
          
          // Write the full blocks
          $pos = 0;
          $data .="\n";

//          for ( $i=0; $i<$n; $i++ ) {
//               self::writeUTF(substr($data,$pos, $pos+self::MAXBLOCKLEN),$bcongFile);
//               $pos +=self::MAXBLOCKLEN;
//          }
//
//          // Write the remaining text
//          if ( $r > 0 ) {
//               self::writeUTF(substr($data,$pos),$bcongFile);
//          }
          self::writeUTF($data,$bcongFile);
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
     //=== Section 4: The filter configurations
     /**
      *
      */
     protected function filterConfiguration($okapiId,$bcongFile){
          
          $filterConfiguration = new editor_Plugins_Okapi_Models_BconfFilter();
          $data=$filterConfiguration->getByOkapiId($okapiId)->toArray();
          
          $count = 0;
          foreach ($data as $filter){
               if($filter['default']==1){
                    $count++;
               }
          }
          self::writeInt($count,$bcongFile);
         
          foreach ($data as $filter){
               if($filter['default']==1){
                    //TODO get dir path
                    $configFilePath=self::OKAPI_BCONF_BASE_PATH.$filter['configId'].'.fprm';
                    $file = fopen( $configFilePath, "r") or die("Unable to open file!");
                    $configData = fread($file, filesize($configFilePath));
                    $this->writeUTF($filter['configId'],$bcongFile);
                    $this->writeUTF($configData,$bcongFile);
               }
          }
     }
     
     /**Section 5: Mapping extensions -> filter configuration id
      * @param $bconfId
      * @param $bcongFile
      */
     protected  function extensionsMapping($okapiId,$bcongFile){
          $filterConfiguration = new editor_Plugins_Okapi_Models_BconfFilter();
          $data=$filterConfiguration->getByOkapiId($okapiId)->toArray();
          $extMap=[];
          $count=0;
          if($data != null){
               foreach ($data as $filter){
                    $extList=explode(",",$filter["extensions"]);
                    foreach ($extList as $extension) {
                         array_push($extMap, array("ext" => $extension, "id" => $filter["configId"]));
                         $count++;
                    }
               }
               self::writeInt($count,$bcongFile); // None
              
               foreach ($extMap as $item){
                    error_log(json_encode($item));
                    self::writeUTF($item["ext"],$bcongFile);
                    self::writeUTF($item["id"],$bcongFile);
               }
          }
          else{
               self::writeInt(0,$bcongFile); // None
          }
     }
     
}