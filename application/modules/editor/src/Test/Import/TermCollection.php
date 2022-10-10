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
 * Represents the api-request configuration for a termcollection
 */
final class TermCollection extends Resource
{
    public string $name;
    public int $collectionId;
    /**
     * @var array|int
     */
    public $customerIds;
    public bool $mergeTerms = true;
    protected string $_tbxFile;
    protected string $_login;

    /**
     * @param string $testClass
     * @param int $index
     * @param string $tbxFile
     * @param string $userlogin
     */
    public function __construct(string $testClass, int $index, string $tbxFile, string $userlogin)
    {
        parent::__construct($testClass, $index);
        $this->_tbxFile = $tbxFile;
        $this->_login = $userlogin;
    }

    /**
     * Adds a term-collection
     * @param Helper $api
     * @throws \Zend_Http_Client_Exception
     */
    public function import(Helper $api, Config $config): void
    {
        $api->login($this->_login);
        // [1] Create empty term collection
        $termCollection = $api->postJson('editor/termcollection', [
            'name' => $this->name,
            'customerIds' => $this->customerIds
        ]);
        $api->getTest()::assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $api->getTest()::assertEquals($this->name, $termCollection->name);
        // Remember collectionId
        $this->collectionId = $termCollection->id;
        // Upload the given TBX
        $api->addFile($this->_tbxFile, $api->getFile($this->_tbxFile), 'application/xml');
        $result = $api->postJson(
            'editor/termcollection/import',
            [
                'collectionId' => $this->collectionId,
                'customerIds' => $this->customerIds,
                'mergeTerms' => $this->mergeTerms
            ]);
        $this->validateResult($result, $api);
    }

    /**
     * @param Helper $api
     */
    public function cleanup(Helper $api, Config $config): void
    {
        if($this->_requested){
            $api->login($this->_login);
            $api->delete('editor/termcollection/' . $this->collectionId);
        }
    }
}
