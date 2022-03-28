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

require_once 'RandomAccessFile.php';
/**
 * Generate new bconf file
 */
class editor_Plugins_Okapi_Bconf_Export
{
    
    const MAXBUFFERSIZE = 1024 * 8;
    const MAXBLOCKLEN = 45000;
    const SIGNATURE = "batchConf";
    const VERSION = 2;
    const NUMPLUGINS = 0;
    /**
     * Export bconf
     */
    public function ExportBconf($okapiId, $bconfBasePath)
    {   
        chdir($bconfBasePath); // so we can access with file name only

        $descFile = "content.json";
        $this->content = [ 'refs' => null, 'fprm' => null ];

        if(file_exists($descFile)){
            $this->content = json_decode(file_get_contents($descFile), (bool)'associative');
        }
        $fileName = 'export.bconf';
        $raf = new editor_Plugins_Okapi_Bconf_RandomAccessFile($fileName, 'wb');
        
        $raf->writeUTF(self::SIGNATURE, false);
        $raf->writeInt(self::VERSION);
        //TODO check the Plugins currentlly not in use
        $raf->writeInt(self::NUMPLUGINS);
        
        //Read the pipeline and extract steps
        $this->processPipeline($raf);
        $this->filterConfiguration($okapiId, $raf);
        $this->extensionsMapping($okapiId, $raf);
        //$raf->fclose();
        
        if(file_exists($fileName)){
            return $fileName;
        } else{
            return null;
        }
    }
    
    protected function processPipeline($raf)
    {
        $pipelineFile = 'pipeline.pln';
        $xml2 = file_get_contents($pipelineFile);
        $pipelineSize = filesize($pipelineFile);
        $resource = fopen($pipelineFile,'rb');
        $xml = fread($resource, $pipelineSize);
        fclose($resource);
        
        $xmlData = new SimpleXMLElement($xml);
        $id = 0;
        foreach ($xmlData as $key) {
            $methods = explode("\n", $key);
            foreach ($methods as $method) {
                if (str_contains($method, 'Path')) {
                    
                    $path = explode("=", $method)[1];
                    $path = str_replace('\\\\', '/', $path);
                    $path = str_replace('\\', '/', $path);
                    
                    $this->harvestReferencedFile($raf, ++$id, basename($path));
                }
            }
        }
        // Last ID=-1 to mark no more references
        $raf->writeInt(-1);
        
        // Write the full blocks
        $pos = 0;
        
        //          for ( $i=0; $i<$n; $i++ ) {
        //               self::writeUTF(substr($xml,$pos, $pos+self::MAXBLOCKLEN),$bconfFile);
        //               $pos +=self::MAXBLOCKLEN;
        //          }
        //
        //          // Write the remaining text
        //          if ( $r > 0 ) {
        //               self::writeUTF(substr($xml,$pos),$bconfFile);
        //          }
        
        $raf->writeInt(1);
        $raf->writeUTF($xml, false);
    }
    
    protected function harvestReferencedFile($raf, $id, $fileName)
    {
        $raf->writeInt($id);
        $raf->writeUTF($fileName, false);
        
        if ($fileName == '') {
            $raf->writeLong(0); // size = 0
            return false;
        }
        //Open the file and read the content
        $file = fopen($fileName, "rb") or die("Unable to open file!");
        
        $fileSize = filesize($fileName);
        $fileContent = fread($file, $fileSize);
       
        $raf->writeLong($fileSize);
        if ($fileSize > 0) {
            $raf->fwrite($fileContent);
        }
        fclose($file);
    }
    //=== Section 4: The filter configurations
    
    /**
     *
     */
    protected function filterConfiguration($okapiId, $raf)
    {   
        $fprms = $this->content['fprm'] ?? glob("*.fprm");
        $raf->writeInt(count($fprms));
        foreach ($fprms as $filterParam) {
                $filename = $filterParam.(str_ends_with($filterParam,'.fprm') ? '' : '.fprm');
                $raf->writeUTF($filterParam, false);
                $raf->writeUTF(file_get_contents($filename), false); //QUIRK: Need additional null byte. Where does it come from in Java?
        }


        return;

        $filterConfiguration = new editor_Plugins_Okapi_Models_BconfFilter();
        $data = $filterConfiguration->getByOkapiId($okapiId);
        $count = 0;
        foreach ($data as $filter) {
            if ($filter['default'] == 1) {
                $count++;
            }
        }
        $raf->writeInt($count);
        
        foreach ($data as $filter) {
            if ($filter['default'] == 1) {
                //TODO get dir path
                $configFilePath = $filter['configId'].'.fprm';
                $file = fopen($configFilePath, "r") or die("Unable to open file!");
                $configData = fread($file, filesize($configFilePath));
                $raf->writeUTF($filter['configId']);
                $raf->writeUTF($configData);
            }
        }
    }
    
    /**Section 5: Mapping extensions -> filter configuration id
     * @param $bconfId
     * @param $raf
     */
    protected function extensionsMapping($okapiId, $raf)
    {
        $extMapFile = "extensions-mapping.txt";
        if(!file_exists($extMapFile)){
            return;
        }
        $extMap = file($extMapFile, FILE_IGNORE_NEW_LINES);
        $amount = count($extMap);
        $extMapBinary = ''; // we'll build up the binary format in memory instead of wirting every line itself to file
        foreach($extMap as $line){
            $extAndConf = explode("\t", $line);
            $extMapBinary .= $raf::toUTF($extAndConf[0]);
            $extMapBinary .= $raf::toUTF($extAndConf[1]);
        }
        $raf->writeInt($amount);
        $raf->fwrite($extMapBinary);

        return;

        $filterConfiguration = new editor_Plugins_Okapi_Models_BconfFilter();
        $data = $filterConfiguration->getByOkapiId($okapiId);
        
        $defaultFilters = new editor_Plugins_Okapi_Models_BconfDefaultFilter();
        $defaultFiltersData = $defaultFilters->loadAll();
        
        $extMap = [];
        $count = 0;
        if ($data != null && count($data) > 0) {
            foreach ($data as $filter) {
                $extList = explode(",", $filter["extensions"]);
                foreach ($extList as $extension) {
                    if(!empty($extension)) {
                        array_push($extMap, array("ext" => $extension, "id" => $filter["configId"]));
                        $count++;
                    }
                }
            }
        }
        if ($defaultFiltersData != null && count($defaultFiltersData) > 0) {
            foreach ($defaultFiltersData as $filter) {
                $extList = explode(",", $filter["extensions"] ?? '');
                foreach ($extList as $extension) {
                    if(!empty($extension)) {
                        array_push($extMap, array("ext" => $extension, "id" => $filter["configId"]));
                        $count++;
                    }
                }
            }            
        }
        if(count($extMap) > 0){
            $raf->writeInt($count); // None
            foreach ($extMap as $item) {
                $raf->writeUTF($item["ext"]);
                $raf->writeUTF($item["id"]);
            }
        } else {
            $raf->writeInt(0); // None
        }
    }
    
}