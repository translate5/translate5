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

namespace MittagQI\Translate5\Test;

use editor_Models_Task;
use Zend_Config;
use Zend_Registry;

/**
 * Class to mock a task-entity including the config
 */
class MockedTask extends editor_Models_Task
{
    private Zend_Config $_testConfig;

    public function getConfig(bool $disableCache = false): Zend_Config
    {
        if (! isset($this->_testConfig)) {
            $this->_testConfig = Zend_Registry::get('config');
        }

        return $this->_testConfig;
    }

    public function setConfig(Zend_Config $config): void
    {
        $this->_testConfig = $config;
    }

    public function setReadOnly(): void
    {
        $this->row->setReadOnly(true);
    }
}
