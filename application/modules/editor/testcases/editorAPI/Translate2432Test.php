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

/**
 * Test if the autoset of the defaults for the okapiBconfDefaultName import/export config works.
 * When making an editor/config request, the okapi plugin will check for .bconf files in the 
 * okapi data directory and those files will be set as defaults option for 
 * runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName and runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName configs.
 * INFO: when new .bconf file is added there, the config should be also added 
 *
 */
class Translate2432Test extends \ZfExtended_Test_ApiTestcase {
    
    /***
     * Currently available bconf files for okapi import/export
     * @var array
     */
    protected static $validExportFile = 'okapi_default_export.bconf';
        
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $appState = self::assertAppState();

        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    /***
     * Test the config autoset
     */
    public function testOkapiConfigDefaults() {
        $result = self::$api->requestJson('editor/config');
        
        $result = json_decode(json_encode($result), true);
        
        //find the default export configs
        $index = array_search('runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName', array_column($result, 'name'));
        $this->assertEquals(true, ($index !== false), 'Missing okapiBconfDefaultName config.');
        
        $default = $result[$index]['default'];

        $this->assertEquals(self::$validExportFile, $default, 'The defaults for config ['.$result[$index]['name'].'] are not as expected');
    }

    public static function tearDownAfterClass(): void {
        //not needed
    }
}
