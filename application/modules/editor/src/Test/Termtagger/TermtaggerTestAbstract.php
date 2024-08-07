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

namespace MittagQI\Translate5\Test\Termtagger;

use editor_Models_Languages;
use editor_Models_Task;
use MittagQI\Translate5\Test\UnitTestAbstract;
use SplFileInfo;
use Zend_Db_Table_Row_Abstract;
use ZfExtended_Factory;

/**
 * OUTDATED base implementation for testing the Termtagger
 */
abstract class TermtaggerTestAbstract extends UnitTestAbstract
{
    public static $parentTestFolderRelativePath = 'application/modules/editor/testcases';

    public static $parentTestFolderAbsolutePath;

    public static $testSuitePath;

    /**
     * @var editor_Models_Task
     */
    protected static $testTask;

    /**
     * @var string
     */
    protected $testSuiteFolderName = 'termtagger';

    /**
     * @var SplFileInfo
     */
    protected static $testfile;

    /**
     * @var string
     */
    protected static $testfilePath;

    /**
     * @var string
     */
    protected static $testcaseSchema;

    /**
     * @var string
     */
    protected static $testcaseSchemaFileName = 'termtaggerTestCaseSchema.xsd';

    /**
     * @var \QueryPath\DOMQuery
     */
    protected static $qpTest;

    /**
     * @var string
     */
    protected static $tbxHash;

    /**
     * @var string
     */
    protected static $tbxData;

    /**
     * @var SplFileInfo
     */
    protected static $tbxFile;

    /**
     * the assertion method to be used
     * @var string
     */
    protected static $assertion;

    /**
     * the description of the test
     * @var string
     */
    public static $description;

    /**
     * the name of the test
     * @var string
     */
    public static $name;

    /**
     * if the test is mandatory or not
     * @var boolean
     */
    public static $mandatory = true;

    /**
     * the source string that is expected to be retrieved by the termtagger
     * @var string
     */
    protected static $expectedSource;

    /**
     * the target string that is expected to be retrieved by the termtagger
     * @var string
     */
    protected static $expectedTarget;

    /**
     * the source string that is retrieved from the termtagger
     * @var string
     */
    protected static $sourceTagged;

    /**
     * the target string that is retrieved from the termtagger
     * @var string
     */
    protected static $targetTagged;

    /**
     * @var Zend_Db_Table_Row_Abstract
     */
    protected static $sourceLangEntity;

    /**
     * @var Zend_Db_Table_Row_Abstract
     */
    protected static $targetLangEntity;

    public static function beforeTests(): void
    {
        self::$testTask = ZfExtended_Factory::get('editor_Models_Task');
    }

    public static function init(SplFileInfo $file)
    {
        self::$testfile = $file;
        self::$testfilePath = $file->getPathname();
        self::$testcaseSchema = self::$testSuitePath . '/' . self::$testcaseSchemaFileName;
    }

    protected static function setTestResources()
    {
        self::$qpTest = qp(self::$testfilePath, ':root', [
            'format_output' => false,
            'encoding' => 'UTF-8',
            'use_parser' => 'xml',
        ]);

        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */

        $sourceLang = strtolower(self::$qpTest->attr('sourceLang'));
        self::$qpTest->attr('sourceLang', $sourceLang);
        self::$sourceLangEntity = $language->loadByRfc5646($sourceLang);

        $targetLang = strtolower(self::$qpTest->attr('targetLang'));
        self::$qpTest->attr('targetLang', $targetLang);
        self::$targetLangEntity = $language->loadByRfc5646($targetLang);

        $qpAssertion = self::$qpTest->find('testcase > assertion');
        self::$assertion = $qpAssertion->attr('type');
        $qpExpectedSource = self::$qpTest->find('testcase > assertion > expectedOutput > source');
        self::$expectedSource = $qpExpectedSource->innerHTML();
        $qpExpectedTarget = self::$qpTest->find('testcase > assertion > expectedOutput > target');
        self::$expectedTarget = $qpExpectedTarget->innerHTML();
        self::$tbxFile = new SplFileInfo(self::$testfile->getPath() . '/' . self::$qpTest->attr('tbxPath'));
        self::$tbxData = file_get_contents(self::$tbxFile->getPathname());
        self::$tbxHash = md5(self::$tbxData);

        $description = self::$qpTest->find('testcase > description');
        self::$description = $description->innerHTML();
        self::$mandatory = (self::$qpTest->attr('mandatory') === 'yes') ? true : false;
        self::$name = self::$qpTest->attr('name');
    }

    public function testPassedAssertionSource()
    {
        $a = self::$assertion . 'Source';
        $this->$a();
    }

    public function testPassedAssertionTarget()
    {
        $a = self::$assertion . 'Target';
        $this->$a();
    }

    protected function assertOutputEqualsSource()
    {
        self::$sourceTagged = $this->removeDataTbxIdFromString(self::$sourceTagged);
        self::$expectedSource = $this->removeDataTbxIdFromString(self::$expectedSource);

        $this->assertEquals(self::$expectedSource, self::$sourceTagged, 'The source content did not meet the expected result. <br><br><b>Expected source</b>: <br><br><pre>' . htmlentities(self::$expectedSource, ENT_HTML5, 'utf-8') . '</pre><br><br><b>Retrieved source</b>: <br><br><pre>' . htmlentities(self::$sourceTagged, ENT_HTML5, 'utf-8') . '</pre>');
    }

    protected function assertOutputEqualsTarget()
    {
        self::$targetTagged = $this->removeDataTbxIdFromString(self::$targetTagged);
        self::$expectedTarget = $this->removeDataTbxIdFromString(self::$expectedTarget);

        $this->assertEquals(self::$expectedTarget, self::$targetTagged, 'The target content did not meet the expected result. <br><br><b>Expected target</b>: <br><br><pre>' . htmlentities(self::$expectedTarget, ENT_HTML5, 'utf-8') . '</pre><br><br><b>Retrieved target</b>: <br><br><pre>' . htmlentities(self::$targetTagged, ENT_HTML5, 'utf-8') . '</pre>');
    }

    protected function removeDataTbxIdFromString($string)
    {
        return preg_replace('#data-tbxid="[^"]+"#', 'data-tbxid=""', $string);
    }
}
