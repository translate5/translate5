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

namespace Translate5\FrontEndMessageBus;
use Translate5\FrontEndMessageBus\Exceptions\ConfigurationException;
/**
 * Wrapper class to the configuration of the socket and message server
 */
class Configuration {
    protected $config;
    public function __construct(string $configFile) {
        if(!file_exists($configFile) || !is_readable($configFile)) {
            throw new ConfigurationException('Config file "'.$configFile.'" is not readable or does not exist! See config.php.example');
        }
        include $configFile;
        /* @var $configuration array */
        if(empty($configuration)) {
            throw new ConfigurationException('Config file "'.$configFile.'" does not contain an array $configuration with the needed configuration values. See config.php.example');
        }
        $this->config = json_decode(json_encode($configuration), null, JSON_FORCE_OBJECT);
    }
    
    /**
     * returns the given config value by name
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->config->$name;
    }
    
    /**
     * returns the given config value by name
     * @param string $name
     * @return mixed
     */
    public function __isset(string $name) {
        return property_exists($this->config, $name);
    }
}