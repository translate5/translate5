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

namespace MittagQI\Translate5\Plugins\SpellCheck\LanguageTool;

use MittagQI\Translate5\PooledService\ServiceAbstract;
use editor_Plugins_SpellCheck_LanguageTool_Adapter;

final class Service extends ServiceAbstract {

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.plugins.SpellCheck.languagetool.url.default',
        'type' => 'list',
        'url' => 'http://languagetool.:8010/v2'
    ];

    protected array $guiConfigurationConfig = [
        'name' => 'runtimeOptions.plugins.SpellCheck.languagetool.url.gui',
        'type' => 'string',
        'url' => 'http://languagetool.:8010/v2'
    ];

    protected array $importConfigurationConfig = [
        'name' => 'runtimeOptions.plugins.SpellCheck.languagetool.url.import',
        'type' => 'list',
        'url' => 'http://languagetool.:8010/v2'
    ];

    protected function customServiceCheck(string $url): bool
    {
        $adapter = $this->getAdapter($url);
        $version = null;
        $result = $adapter->testServerUrl($url, $version);
        $this->version = (!empty($version)) ? strval($version) : null;
        return $result;
    }

    /**
     * Creates an SpellCheck Adapter
     * @param string $serviceUrl
     * @return editor_Plugins_SpellCheck_LanguageTool_Adapter
     */
    public function getAdapter(string $serviceUrl): editor_Plugins_SpellCheck_LanguageTool_Adapter
    {
        return new editor_Plugins_SpellCheck_LanguageTool_Adapter($serviceUrl);
    }
}
