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

namespace MittagQI\Translate5\Test\Import;

use MittagQI\Translate5\Test\Api\Helper;

/**
 * Represents the api-request configuration for a bconf upload
 */
final class Bconf extends Resource
{
    public string $name;

    public ?int $customerId = null;

    private string $_uploadFile;

    private bool $_failOnError = true;

    private bool $_uploadFailed = false;

    /**
     * @param string $testClass
     * @param int $index
     * @param string $name
     * @param string $bconfFileName
     */
    public function __construct(string $testClass, int $index, string $name, string $bconfFileName)
    {
        parent::__construct($testClass, $index);
        $this->name = $name;
        $this->_uploadFile = $bconfFileName;
    }

    /**
     * Special API to not fail on error during import. Can be used e.g. to test expected failures
     * @return $this
     */
    public function setNotToFailOnError(): Bconf
    {
        $this->_failOnError = false;
        return $this;
    }

    /**
     * When an Bconf was imported with ::setNotToFailOnError() called, this retrieves if the upload really failed
     * @return bool
     */
    public function hasUploadFailed(): bool
    {
        return $this->_uploadFailed;
    }

    /**
     * Adds a term-collection
     * @param Helper $api
     * @param Config $config
     * @throws Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function import(Helper $api, Config $config): void
    {
        if($this->_requested){
            throw new Exception('You cannot import a Bconf twice.');
        }

        $api->addFile('bconffile', $api->getFile($this->_uploadFile), 'application/octet-stream');
        $params = $this->getRequestParams();
        if(empty($params['customerId'])){
            unset($params['customerId']);
        }
        $result = $api->postJson('editor/plugins_okapi_bconf/uploadbconf', $params, null, false, !$this->_failOnError);
        // in case we want the bonf to fail we simply apply the result without validation
        if(!$this->_failOnError && is_object($result) && !property_exists($result, 'id')){
            $this->applyResult($result);
            $this->_uploadFailed = true;
        } else {
            $this->validateResult($result, $api);
        }
    }

    /**
     * @param Helper $api
     * @param Config $config
     */
    public function cleanup(Helper $api, Config $config): void
    {
        if($this->_requested && !$this->_uploadFailed){
            $api->delete('editor/plugins_okapi_bconf/' . $this->getId());
        }
    }
}
