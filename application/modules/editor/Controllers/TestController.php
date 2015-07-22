<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+ 
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 * 
 */
class Editor_TestController extends ZfExtended_Controllers_Action  {
    /**
     * @var ReflectionClass of editor_Test_ACTIONPASSED
     */
    protected $testcase;
    
    /**
     * @var ReflectionClass of editor_Test_ACTIONPASSED_Preparation
     */
    protected $testcasePreparation;
    
    /**
     * @var FilesystemIterator
     */
    protected $testIterator;
    /**
     *
     * @var string
     */
    protected $errorColor = 'red';
    /**
     *
     * @var array collects which xml failed and which succeeded
     */
    protected $xmlResultSummary = array();
    public function init() {
        parent::init();
        $class = ucfirst($this->getRequest()->getActionName());
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $this->testcase = new ReflectionClass('editor_Test_'.$class);
        $testcaseProperties = $this->testcase->getDefaultProperties();
        $parentTestFolderAbsolutePath = APPLICATION_PATH.'/../'.$testcaseProperties['parentTestFolderRelativePath'];
        $testSuitePath = $parentTestFolderAbsolutePath.'/'.$testcaseProperties['testSuiteFolderName'];
        $this->testcase->setStaticPropertyValue('parentTestFolderAbsolutePath', $parentTestFolderAbsolutePath);
        $this->testcase->setStaticPropertyValue('testSuitePath', $testSuitePath);
        
        $this->testcasePreparation = new ReflectionClass('editor_Test_'.$class.'_Preparation');
        $testcasePreparationProperties = $this->testcasePreparation->getDefaultProperties();
        $parentTestFolderAbsolutePath = APPLICATION_PATH.'/../'.$testcasePreparationProperties['parentTestFolderRelativePath'];
        $testSuitePath = $parentTestFolderAbsolutePath.'/'.$testcasePreparationProperties['testSuiteFolderName'];
        $this->testcasePreparation->setStaticPropertyValue('parentTestFolderAbsolutePath', $parentTestFolderAbsolutePath);
        $this->testcasePreparation->setStaticPropertyValue('testSuitePath', $testSuitePath);
        
        
        $this->testIterator = new RecursiveDirectoryIterator($testSuitePath,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS);
    }
    
    public function termtaggerAction() {
        $this->loopThroughTestXmlFiles();
        $this->echoResultSummary();
    }
    
    protected function loopThroughTestXmlFiles() {
        while ($file = $this->getNextTestXml()) {
            $resultPreparation = $this->runTests($file,  $this->testcasePreparation);
            if(!$resultPreparation->wasSuccessful()){
                $this->echoResults($resultPreparation,$file);
                echo '<p style="color:red"><b>There have been errors in Test-Preperation. Main test not started.</b></p>';
                continue;
            }
            
            $result = $this->runTests($file,  $this->testcase);
            $this->echoResults($result,$file);
            $this->xmlResultSummary[$file->getFilename()]=  $this->errorColor;
        }
    }
    
    protected function echoResultSummary() {
        echo "<h2>Summary of Results</h2>";
        foreach ($this->xmlResultSummary as $filename => $color) {
            echo '<b style="color: '.$color.'">'.$filename.'</b><br>';
        }
    }
    /**
     * 
     * @param PHPUnit_Framework_TestResult $result
     * @return boolean
     */
    protected function echoResults(PHPUnit_Framework_TestResult $result,\SplFileInfo $file) {
        echo "<h2>".$file->getFilename()."/ ".$this->testcase->getProperty('name')->getValue()."</h2>";
        echo "<h3>Description: ".$this->testcase->getProperty('description')->getValue()."</h3>";
            
        /* @var $result PHPUnit_Framework_TestResult */
        $errors = $result->errors();
        $failures = $result->failures();
        
        $errorCount = count($errors);
        $failureCount = count($failures);
        
        if($errorCount === 0 && $failureCount === 0){
            $this->errorColor = 'green';
            echo '<p style="color:'.$this->errorColor.'"><b>Test passed</b></p>';
            return true;
        }
        
        if(!$this->testcase->getProperty('mandatory')->getValue()){
            $this->errorColor = 'orange';
            echo '<p style="color:'.$this->errorColor.'"><b>Nice to have (not mandatory)</b></p>';
        }
        else{
            $this->errorColor = 'red';
            echo '<p style="color:'.$this->errorColor.'"><b>Test is mandatory</b></p>';
        }

        if($errorCount>0){
            echo '<p style="color:'.$this->errorColor.'">There have been errors in the execution. Test '.$file->getFilename().' could not run.</p><ol style="color:'.$this->errorColor.'">';
            foreach ($errors as $key => $error ) {
                /* @var $failure PHPUnit_Framework_TestFailure */
                echo '<li>'.$error->exceptionMessage().'</li>';
            }
            echo "</ol>";
            return false;
        }

        
        if($failureCount>0){
            echo '<p style="color:'.$this->errorColor.'">Main Test failed.</p><ol style="color:'.$this->errorColor.'">';
            foreach ($failures as $key => $failure ) {
                /* @var $failure PHPUnit_Framework_TestFailure */
                echo '<li>'.$failure->exceptionMessage().'</li>';
            }
            echo "</ol>";
            return false;
        }
        return true;
    }
    /**
     * 
     * @param \SplFileInfo $file
     * @param \ReflectionClass $class
     * @return $result PHPUnit_Framework_TestResult 
     */
    protected function runTests(\SplFileInfo $file, \ReflectionClass $class) {
        $initMethod = $class->getMethod('init');
        $initMethod->invoke(null,$file);
        $testsuite = new PHPUnit_Framework_TestSuite($class);

        /* @var $testsuite PHPUnit_Framework_TestSuite */
        return $testsuite->run();
    }
    
    /**
     * @return SplFileInfo | false
     */
    protected function getNextTestXml() {
        if(!$this->testIterator->valid()){
            return false;
        }
        $file = $this->testIterator->current();
        /* @var $file SplFileInfo */
        while(($file->isDir()||$file->getExtension()!=='testcase') && $this->testIterator->valid()){
            
            $this->testIterator->next();
            if(!$this->testIterator->valid()){
                break;
            }
            $file = $this->testIterator->current();
        }
        $this->testIterator->next();
        return ($file->getExtension() === 'testcase')?$file:false;
    }
}

