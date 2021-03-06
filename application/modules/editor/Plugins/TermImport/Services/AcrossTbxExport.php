<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */
/**
 * ----------------------------------------------------------------------------
 * the connector class
 * ----------------------------------------------------------------------------
 */

class TbxAcrossSoapConnector extends AcrossSoapConnector {
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

class AcrossSoapConnector extends SoapClient  {
    
    protected static $ACROSS_API_URL_WSDL = '/crossAPI/crossAPI.wsdl';
    protected static $ACROSS_API_URL_ACTION = 'http://tempuri.org/crossAPI/action/';
    
    
    /**
     * Container to hold the securityToken generated by Across.
     * @var string
     */
    protected $securityToken = NULL;
    
    /**
     * Container to hold the current soap-action. Will be set in __soapCall() (if needed) and used in __doRequest()
     * @var string
     */
    protected $currentAction = NULL;
    
    
    /**
     * Initialize the soap connection and load the across securityToken which is need for every soap-request
     */
    public function __construct($apiUrl, $apiLogin, $apiPass) {
        $soapConfig = [
                        //'location' => self::$ACROSS_API_URL_WSDL,
                        //'uri' => self::$ACROSS_API_URL_WSDL, // TODO: what is this parameter for?
                        'soap_version' => SOAP_1_1,
                        'trace' => 1,
                        'login' => $apiLogin,
                        'password' => $apiPass,
        ];
        try {
            parent::__construct($apiUrl.self::$ACROSS_API_URL_WSDL, $soapConfig);
            $this->createSecurityToken($apiLogin,$apiPass);
        }
        catch (SoapFault $e) {
            throw new AcrossSoapConnectorException('Error on connecting to Across under "'.self::$ACROSS_API_URL_WSDL.'"', $e);
        }
    }
    
    /**
     * Create the Across securityToken and store it in $this->securityToken.
     * This securityToken is needed for authorisation in each and every crossApi-call.
     * on error throws AcrossSoapConnectorException
     */
    protected function createSecurityToken($apiLogin,$apiPassword) {
        $result = $this->__soapCall('Authorization.CreateSecurityToken', [$apiLogin, $apiPassword]);
        
        if ($result->errorcode) {
            throw new AcrossSoapConnectorException('can not create Across security token', $result);
        }
        
        $this->securityToken = $result->data;
    }
    
    
    /**
     * This function does 2 things:<br>
     * <ol><li>Handle Across-Objects:<br>
     * In across all functions are capsulated in Objects, e.g. TaskManager.GetJobStatus. Here the Acroos-object is "TaskManager" and the actual function called is "GetJobStatus".
     * The PHP soap-client does not handle this object-capsulated-funtion correct. So here all 
     * </li><li>"Recall":<br>
     * All soap calls that lead to a SoapFault-Exception or to an across-answer-error must/should be repeated up to 10 times.
     * </li></ol>
     * 
     * {@inheritDoc}
     * @see SoapClient::__soapCall()
     */
    public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null, $iteration = 0) {
        // reset an previously set action.
        if ($iteration == 0) {
            $this->currentAction = NULL;
        }
        
        // Check if a function with object is sumbitted, e.g. 'TaskManager.GetJobStatus'.
        // If so, the action for the request must be stored and the function must be reseted to the pure function-name (without the object)
        if (strpos($function_name, '.')) {
            $this->currentAction = self::$ACROSS_API_URL_ACTION.$function_name;
            
            $tempFunction = explode('.', $function_name);
            $function_name = $tempFunction[1];
        }
        
        // try call for 10 times before stopping on a SoapFault exception
        try {
            return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
        }
        catch (SoapFault $e) {
            if ($iteration >= 10) {
                $tempDetails = ['function' => $function_name,
                                'arguments' => $arguments,
                                'options' => $options,
                                'input_headers' => $input_headers,
                                'output_headers' => $output_headers,
                                'exception' => $e
                ];
                throw new AcrossSoapConnectorException('Error on communication with Across', $tempDetails);
            }
            usleep(300000); // wait for a while until the next call is submitted (!unit is microseconds, so 1.000.000 is one second)
            return $this->__soapCall($function_name, $arguments, $options, $input_headers, $output_headers, ++$iteration);
        }
    }
    
    /**
     * Handles Across-Objects. For more information see doc of $this->__soapCall()
     * {@inheritDoc}
     * @see SoapClient::__doRequest()
     */
    public function __doRequest($request, $location, $action, $version, $one_way=0) {
        if (!is_null($this->currentAction)) {
            $action = $this->currentAction;
        }
        
        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
    
    /**
     * Create a temporary file on the Across server
     * @throws AcrossSoapConnectorException
     */
    protected function createFileStream($filename) {
        // create a temporary file
        $params = [$this->securityToken, $filename];
        $result = $this->__soapCall('FileManager.CreateFileStream', $params);
        
        if ($result->errorcode) {
            throw new AcrossSoapConnectorException('can not create temporary filestream', $result);
        }
        
        return $fileGuid = $result->data;
    }
    
    /**
     * Get the content of the file $fileGuid created from Across.
     * 
     * @param string $fileGuid
     * @return string (binary)
     */
    protected function getFileFromServer($fileGuid) {
        $params = [$this->securityToken, $fileGuid];
        $result = $this->__soapCall('FileManager.GetFileFromServer', $params);
        
        if ($result->errorcode) {
            throw new AcrossSoapConnectorException('can not read from file with fileguid '.$fileGuid, $result);
        }
        
        return $result->data;
    }
    
    /**
     * Remove a temporary file on teh Across server
     * @param string $fileGuid
     */
    protected function removeFileFromServer($fileGuid) {
        // remove the temporary file
        $params = [$this->securityToken, $fileGuid];
        $result = $this->__soapCall('FileManager.RemoveFileFromServer', $params);
    }
    
    /**
     * Wait until the job of the across-API-object $acrossApiObject with guid $guid is finished.
     * on error throws AcrossSoapConnectorException
     *  
     * @param string $acrossApiObject<br>
     * e.g. 'TaskManager', 'DocumentManager', ... an element of the list under https://wiki.across.net/display/ASCS/crossAPI+SI+Objects+Description
     * @param string $guid
     * 
     * @return TRUE
     */
    protected function waitUntilJobIsFinished($acrossApiObjectName, $guid) {
        $params = [$this->securityToken, $guid];
        $result = $this->__soapCall($acrossApiObjectName.'.GetJobStatus', $params);
        
        if ($result->errorcode) {
            throw new AcrossSoapConnectorException('can not wait until job "'.$acrossApiObjectName.'" is finished)', $result);
        }
        
        // if job is still running
        if ($result->data === 0) {
            usleep(500000); // wait for 0.5 seconds
            return $this->waitUntilJobIsFinished($acrossApiObjectName, $guid);
        }
        sleep(1);
        
        return TRUE;
    }
    
    
}


/**
 * Special AcrossSoapConnector Exception extends the normal Exception
 */
class AcrossSoapConnectorException extends Exception {
    public function __construct($text, $additionalInformations = NULL) {
        if (!is_null($additionalInformations)) {
            $text .= "\n\n".print_r($additionalInformations, true);
        }
        parent::__construct($text);
    }
}
