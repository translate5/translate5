<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\TermImport\Services\Across;

class TbxSoapConnector extends SoapConnector
{
    /**
     * Get the tbx file from the Acros server.
     * https://wiki.across.net/display/ASCS/CrossTermManager.ExportTBX3
     * 
     * @param string $configFile
     * @throws Exception
     */
    public function getTbx($configFile) {
        $tempReturn = FALSE;
        // create a temp file
        $fileGuid = $this->createFileStream('export.tbx');
        
        try {
            // detect path and name of the temp file
            $params = [$this->securityToken, $fileGuid];
            $result = $this->__soapCall('FileManager.GetPathToFile', $params);
            $tempFilename = $result->data;
            
            // do the export into the temp file
            $tempExportConfig = file_get_contents($configFile);
            $params = [$this->securityToken, $tempFilename, $tempExportConfig];
            $result = $this->__soapCall('CrossTermManager.ExportTBX3', $params);
            $jobGuid = $result->data;
            
            if (!$this->waitUntilJobIsFinished('CrossTermManager', $jobGuid)) {
                throw new Exception('can not export TBX into file "'.$tempFilename.'" (wait until job is finished)', $result);
            }
            
            // when tbx export is finished, get file content from server
            // !! if tbx-file is very large, the function may run out of memory !!
            $tempReturn = $this->getFileFromServer($fileGuid);
        }
        catch(Exception $e) {
            // do nothing... but the temp file must be removed in case of error
            error_log(__FILE__.'::'.__LINE__.'; '.__CLASS__.' -> '.__FUNCTION__.'; '.print_r($e, 1));
        }
        // remove the temp file
        $this->removeFileFromServer($fileGuid);
        
        return $tempReturn;
    }
    
}
