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

namespace MittagQI\Translate5\Service;

use Throwable;
use Zend_Db_Statement_Exception;
use Zend_Http_Client;
use ZfExtended_Exception;
use ZfExtended_Factory;

/**
 * Represents the dockerized T5-app itself
 */
final class Php extends DockerServiceAbstract {

    /**
     * Service is relevant only for fully dockerized installations
     * @var bool
     */
    protected bool $mandatory = false;

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.worker.server',
        'type' => 'string',
        'url' => 'http://php.:80',
        'healthcheck' => '/editor/index/applicationstate',
        'healthcheckIsJson' => true
    ];

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     */
    public function check(): bool {
        $maintenance = new \ZfExtended_Models_Installer_Maintenance();
        if($maintenance->isActive()) {
            $this->warnings[] = 'Instance is in maintenance';
            return false;
        }
        return parent::check();
    }

    protected function findVersionInResponseBody(string $responseBody, string $serviceUrl): ?string
    {
        // we add a warning if the result is no proper JSON, presumably the cron-IP is not properly set up then
        if (trim($responseBody) === 'null') {
            $this->warnings[] = 'Check config runtimeOptions.cronIP - may be configured wrong.';
            return null;
        }
        $state = json_decode($responseBody);
        if ($state) {
            $version = $state->version ?? null;
            if (!empty($state->branch)) {
                $version = ($version == null) ? $state->branch : $version.' '.$state->branch;
            }
            return $version;
        }
        return null;
    }
}
