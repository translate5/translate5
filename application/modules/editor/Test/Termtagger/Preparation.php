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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * Tests / validates the Testcase described in the .testcase XML file
 */
class editor_Test_Termtagger_Preparation extends editor_Test_Termtagger_Abstract{

    /**
     * Validates the xml testcase file against schema
     * 
     */
    public function testValidatecase() {
        $validSchema = false;
        $doc = new DOMDocument();
        $validxml = $doc->load(self::$testfilePath);
        
        if($validxml){
            $validSchema = $doc->schemaValidate(self::$testcaseSchema);
        }
        
        $this->assertTrue($validxml && $validSchema,
            'The testcase '.self::$testfilePath.' did not valid against '.  self::$testcaseSchema);
    }
    /**
     * Validates mandatory settings of the testcase
     * @depends testValidatecase
     */
    public function testValidatecasesettings() {
        self::setTestResources();
        $mandatory = strtolower(self::$qpTest->attr('mandatory'));
        self::$qpTest->attr('mandatory', $mandatory);
        $this->assertTrue($mandatory === 'yes'||$mandatory==='no','The testfile '.
                self::$testfilePath.' does not contain a valid value for "mandatory".');
        
        $sourceStringMatch = strtolower(self::$qpTest->attr('sourceStringMatch'));
        //we know, it is not recommended to have more than one assertion in a test, but
        //in this case it makes things easier. Anyhow, this preconditions should
        //all be matched, if the testcase.xml-files are correctly written
        $this->assertTrue($sourceStringMatch === '1'||$sourceStringMatch ==='0','The testfile '.
                self::$testfilePath.' does not contain a valid value for "sourceStringMatch".');
        
        $targetStringMatch = strtolower(self::$qpTest->attr('targetStringMatch'));
        $this->assertTrue($targetStringMatch === '1'||$targetStringMatch ==='0','The testfile '.
                self::$testfilePath.' does not contain a valid value for "targetStringMatch".');
        
        $this->assertNotNull(self::$sourceLangEntity,'The testfile '.self::$testfilePath.
                ' does not have a valid value for sourceLang - one that is known to the executing translate5 instance.');
        $this->assertNotNull(self::$targetLangEntity,'The testfile '.self::$testfilePath.
                ' does not have a valid value for targetLang - one that is known to the executing translate5 instance.');
        
        $tbxFilePath = self::$testfile->getPath().'/'.self::$qpTest->attr('tbxPath');
        $this->assertFileExists($tbxFilePath, 'For the testfile '.self::$testfilePath.
                ' the defined tbx-file with the path '.$tbxFilePath.' does not exist');
        
        $assertion = self::$qpTest->find('testcase > assertion');
        $this->assertTrue(method_exists('editor_Test_Termtagger', $assertion->attr('type').'Source'),'The assertion-Method '.$assertion->attr('type').'Source is not specified in the termtagger-test-class.');
        $this->assertTrue(method_exists('editor_Test_Termtagger', $assertion->attr('type').'Target'),'The assertion-Method '.$assertion->attr('type').'Target is not specified in the termtagger-test-class.');
    }
}