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
 * Represents the general api-request configuration for a language-resource
 */
abstract class LanguageResource extends AbstractResource
{
    public const OPEN_TM2 = 'opentm2';

    public const DEEPL = 'deepl';

    public const TERM_COLLECTION = 'termcollection';

    public const DUMMY_TM = 'dummytm';

    public const ZDemo_MT = 'zdemomt';

    public const MICROSOFT_TRANSLATOR = 'microsofttranslator';

    public const GOOGLE_TRANSLATE = 'googletranslate';

    public string $name;

    public array $customerIds = [];

    public array $customerUseAsDefaultIds = [];

    public array $customerWriteAsDefaultIds = [];

    protected string $resourceId;

    protected string $serviceType;

    protected string $serviceName;

    protected ?string $_uploadFile = null;

    protected string $_deleteRoute = 'editor/languageresourceinstance/';

    protected bool $_associateToTasks = true;

    public function __construct(string $testClass, int $index)
    {
        parent::__construct($testClass, $index);
        $this->resourceId = $this->createResourceId($index);
    }

    /**
     * Creates the resource-id
     */
    protected function createResourceId(int $resourceIndex): string
    {
        return $this->serviceType;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    /**
     * Adds the upload for the language resource
     * @return $this
     */
    public function addUploadFile(string $resourceFileName): LanguageResource
    {
        $this->_uploadFile = $resourceFileName;

        return $this;
    }

    /**
     * @return $this
     */
    public function addDefaultCustomerId(int $customerId, bool $useForWriteAccess = false, bool $resourceIsTaskAssociated = false): LanguageResource
    {
        if (! in_array($customerId, $this->customerUseAsDefaultIds)) {
            $this->customerUseAsDefaultIds[] = $customerId;
        }
        if ($useForWriteAccess && ! in_array($customerId, $this->customerWriteAsDefaultIds)) {
            $this->customerWriteAsDefaultIds[] = $customerId;
        }
        $this->_associateToTasks = $resourceIsTaskAssociated;

        return $this;
    }

    /**
     * @return $this
     */
    public function setIsNotTaskAssociated(): LanguageResource
    {
        $this->_associateToTasks = false;

        return $this;
    }

    public function isTaskAssociated(): bool
    {
        return $this->_associateToTasks;
    }

    /**
     * @throws \Zend_Http_Client_Exception
     */
    public function import(Helper $api, Config $config): void
    {
        if ($this->_requested) {
            throw new Exception('You cannot import a ' . get_class($this) . ' twice.');
        }
        $result = $api->addResource($this->getRequestParams(), $this->_uploadFile, true);
        $this->validateResult($result, $api);
    }

    /**
     * Removes the languageresource from the system
     */
    public function cleanup(Helper $api, Config $config): void
    {
        if ($this->_requested) {
            // use forced flag to remove all task assignments. In case forced flag is NOT used, this will throw an
            // exception if there are still task assignments connected to this resource
            $api->delete($this->_deleteRoute . $this->getId() . '?forced=true');
        }
    }
}
