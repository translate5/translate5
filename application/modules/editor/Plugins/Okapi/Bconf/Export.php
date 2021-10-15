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
class editor_Plugins_Okapi_Bconf_Export
{
    const OKAPI_BCONF_BASE_PATH = 'D:/okapi/test/';
    const MAXBUFFERSIZE = 1024 * 8;
    const MAXBLOCKLEN = 45000;
    const SIGNATURE = "batchConf";
    const VERSION = 2;
    const  NUMPLUGINS = 0;
    const PIPELINEFILE = "D:/okapi/pipeline.pln";
    const EXTFILESDIR = "G:/projects/Marc/projects/translate5/application/modules/editor/Plugins/Okapi/data/okapi-import-bconf-generation/testfiles";
    
    protected $util;
    public function __construct(){
        $this->util = new editor_Plugins_Okapi_Bconf_Util();
    }
    /**
     * Export bconf
     */
    public function ExportBconf($okapiName, $okapiId, $bconfFilesPath)
    {
        
        $bconfFile = fopen($bconfFilesPath.'/'.$okapiName.'.bconf', "w") or die("Unable to open file!");
        error_log($bconfFilesPath.$okapiName.'.bconf');
        $this->util->writeUTF(self::SIGNATURE, $bconfFile);
        $this->util->writeInt(self::VERSION, $bconfFile);
        //TODO check the Plugins currentlly not in use
        $this->util->writeInt(self::NUMPLUGINS, $bconfFile);
        
        //Read the pipeline and extract steps
        $this->processPipeline(self::PIPELINEFILE, $bconfFile);
        $this->filterConfiguration($okapiId, $bconfFile);
        $this->extensionsMapping($okapiId, $bconfFile);
        fclose($bconfFile);
    }
    
    protected function processPipeline($pipeLine, $bconfFile)
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
                    self::harvestReferencedFile($bconfFile, ++$id, explode("=", $method)[1]);
                }
            }
        }
        // Last ID=-1 to mark no more references
        $this->util->writeInt(-1, $bconfFile);
        
        $withOutNewLine = preg_replace('/[\n]{0,}/m', '', $data);
        $fileSize = strlen($withOutNewLine);
        //++$fileSize;
        $r = (int)($fileSize % self::MAXBLOCKLEN);
        $n = (int)($fileSize / self::MAXBLOCKLEN);
        // Number of blocks
        $count = $n + (($r > 0) ? 1 : 0);
        $this->util->writeInt($count, $bconfFile);
        
        // Write the full blocks
        $pos = 0;
        
        //          for ( $i=0; $i<$n; $i++ ) {
        //               self::writeUTF(substr($data,$pos, $pos+self::MAXBLOCKLEN),$bconfFile);
        //               $pos +=self::MAXBLOCKLEN;
        //          }
        //
        //          // Write the remaining text
        //          if ( $r > 0 ) {
        //               self::writeUTF(substr($data,$pos),$bconfFile);
        //          }
        
        $this->util->writeUTF($withOutNewLine, $bconfFile);
        fclose($pipeLineFileOpen);
    }
    
    protected function harvestReferencedFile($bconfFile, $id, $refPath)
    {
        $this->util->writeInt($id, $bconfFile);
        $path = parse_url($refPath, PHP_URL_PATH);
        
        $this->util->writeUTF(basename($path), $bconfFile);
        
        if ($refPath == '') {
            $this->util->writeLong(0, $bconfFile); // size = 0
            return false;
        }
        //Open the file and read the content
        $file = fopen($refPath, "r") or die("Unable to open file!");
        
        $fileSize = filesize($refPath);
        $fileContent = fread($file, $fileSize);
        
        $this->util->writeLong($fileSize, $bconfFile);
        if ($fileSize > 0) {
            fwrite($bconfFile, $fileContent);
        }
        fclose($file);
    }
    //=== Section 4: The filter configurations
    
    /**
     *
     */
    protected function filterConfiguration($okapiId, $bconfFile)
    {
        
        $filterConfiguration = new editor_Plugins_Okapi_Models_BconfFilter();
        $data = $filterConfiguration->getByOkapiId( $okapiId);
        $count = 0;
        foreach ($data as $filter) {
            if ($filter['default'] == 1) {
                $count++;
            }
        }
        $this->util->writeInt($count, $bconfFile);
        
        foreach ($data as $filter) {
            if ($filter['default'] == 1) {
                //TODO get dir path
                $configFilePath = self::OKAPI_BCONF_BASE_PATH . $filter['configId'].'.fprm';
                error_log($configFilePath);
                $file = fopen($configFilePath, "r") or die("Unable to open file!");
                $configData = fread($file, filesize($configFilePath));
                $this->util->writeUTF($filter['configId'], $bconfFile);
                $this->util->writeUTF($configData, $bconfFile);
            }
        }
    }
    
    /**Section 5: Mapping extensions -> filter configuration id
     * @param $bconfId
     * @param $bconfFile
     */
    protected function extensionsMapping($okapiId, $bconfFile)
    {
        
        $filterConfiguration = new editor_Plugins_Okapi_Models_BconfFilter();
        $data = $filterConfiguration->getByOkapiId($okapiId);
        
        $defaultFilters = new editor_Plugins_Okapi_Models_BconfDefaultFilter();
        $defaultFiltersData = $defaultFilters->loadAll();
        
        $extMap = [];
        $count = 0;
        if ($data != null) {
            foreach ($data as $filter) {
                $extList = explode(",", $filter["extensions"]);
                foreach ($extList as $extension) {
                    if(!empty($extension)) {
                        array_push($extMap, array("ext" => $extension, "id" => $filter["configId"]));
                        $count++;
                    }
                }
            }
            foreach ($defaultFiltersData as $filter) {
                $extList = explode(",", $filter["extensions"]);
                foreach ($extList as $extension) {
                    if(!empty($extension)) {
                        array_push($extMap, array("ext" => $extension, "id" => $filter["configId"]));
                        $count++;
                    }
                }
            }
            $this->util->writeInt($count, $bconfFile); // None
            
            foreach ($extMap as $item) {
                $this->util->writeUTF($item["ext"], $bconfFile);
                $this->util->writeUTF($item["id"], $bconfFile);
            }
        } else {
            $this->util->writeInt(0, $bconfFile); // None
        }
    }
    
}