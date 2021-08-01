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
 * Tests the User Auth API
 */
class AllTablesEventCodesTest extends \ZfExtended_Test_ApiTestcase {
    
    protected static $appRoot;
    
    public static function setUpBeforeClass(): void {
        self::$appRoot = explode('/', getcwd());
        self::$appRoot = join('/', array_splice(self::$appRoot, 0, -4));
    }
    
    public function testTables() {
        $result = editor_Plugins_ArchiveTaskBeforeDelete_DbTables::runTest();
        $msg = 'The following tables are not in sync with the table list in editor_Plugins_ArchiveTaskBeforeDelete_DbTables!';
        $msg .= "\n".print_r($result, true);
        $this->assertEmpty($result, $msg);
    }
    
    public function testEcodesUsed() {
        /*
         ** Check the used error numbers
         */
        chdir(self::$appRoot);
        $cmd = 'find -iname "*.php" -or -iname "*.phtml" | xargs grep --colour=auto -E "[\\"\']E[0-9]{4,4}[\\"\']"';
        $result = null;
        $matches = [];
        exec($cmd, $result);
        
        //duplication recognition not possible in source code since error codes are used multiple times: on usage place and on definition place
        $collectedCodes = [];
        $collectedLines = [];
        foreach ($result as $line) {
            preg_match_all('/["\'](E[0-9]{4,4})["\']/', $line, $matches);
            foreach($matches[1] as $code) {
                $collectedCodes[] = $code;
                settype($collectedLines[$code], 'array');
                $collectedLines[$code][] = $line;
            }
        }
        
        $collectedCodes = array_unique($collectedCodes);
        $docuUrl = "https://confluence.translate5.net/display/TAD/EventCodes";
        $text = file_get_contents($docuUrl);
        $codes = [];
        preg_match_all("/(E[0-9]{4})/", $text, $codes);
        $documentedCodes = array_unique($codes[0]);
        
        $unusedInDocu = array_diff($documentedCodes, $collectedCodes);
        $notFoundInDocu = array_diff($collectedCodes, $documentedCodes);
        
        if(!empty($unusedInDocu)) {
            $this->addWarning("\n\nWarning: the following Errorcodes are in the documentation, but not in the PHP code:\n".print_r($unusedInDocu, true));
        }
        
        if(!empty($notFoundInDocu)) {
            $msg = "\n\nError: the following Errorcodes are in the PHP Code, but not found in the Online Documentation:\n";
            $msg .= $docuUrl."\n\n";
            foreach($notFoundInDocu as $notFound) {
                $msg .= $notFound." => ";
                $msg .= print_r($collectedLines[$notFound],1);
                $msg .= "\n\n";
            }
            $this->fail($msg);
        }
    }
}