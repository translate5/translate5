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
    
    /**
     * @var string
     */
    protected $testSuitePath;
    
    public function init() {
        parent::init();
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }
    
    public function termtaggerAction() {
        $class = ucfirst($this->getRequest()->getActionName());
        $this->testcase = new ReflectionClass('editor_Test_'.$class);
        $testcaseProperties = $this->testcase->getDefaultProperties();
        $parentTestFolderAbsolutePath = APPLICATION_PATH.'/../'.$testcaseProperties['parentTestFolderRelativePath'];
        $this->testSuitePath = $parentTestFolderAbsolutePath.'/'.$testcaseProperties['testSuiteFolderName'];
        $this->testcase->setStaticPropertyValue('parentTestFolderAbsolutePath', $parentTestFolderAbsolutePath);
        $this->testcase->setStaticPropertyValue('testSuitePath', $this->testSuitePath);
        
        $this->testcasePreparation = new ReflectionClass('editor_Test_'.$class.'_Preparation');
        $testcasePreparationProperties = $this->testcasePreparation->getDefaultProperties();
        $parentTestFolderAbsolutePath = APPLICATION_PATH.'/../'.$testcasePreparationProperties['parentTestFolderRelativePath'];
        $testSuitePath = $parentTestFolderAbsolutePath.'/'.$testcasePreparationProperties['testSuiteFolderName'];
        $this->testcasePreparation->setStaticPropertyValue('parentTestFolderAbsolutePath', $parentTestFolderAbsolutePath);
        $this->testcasePreparation->setStaticPropertyValue('testSuitePath', $testSuitePath);
        
        $this->testIterator = new RecursiveDirectoryIterator($testSuitePath,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS);
        
        ob_clean(); //remove bin/env php call
        echo "<h1>TermTagger Tests; ".  date(DATE_RFC2822)."</h1>";
        $this->printTermtaggerSummary();
        $this->firstResults = ob_get_clean();
        
        $this->loopThroughTestXmlFiles($this->getParam('filter', null));
        $this->echoResultSummary();
    }
    
    /**
     * Checks if the configured termTaggers are running and have the assumed version.
     * The assumed version is stored in the file testcases/ACTIONPASSED/TermTaggerServerVersion.htm as plain string direct copied from http://termtagger:9001:termTagger
     */
    protected function printTermtaggerSummary() {
        echo '<h2>Tested TermTagger(s)</h2>';
        $termtaggers = Zend_Registry::get('PluginManager')->get('TermTagger')->termtaggerState();
        $taggerUsage = array();
        foreach($termtaggers->configured as $type => $taggers){
            foreach ($taggers as $tagger) {
                if(!isset($taggerUsage[$tagger])) {
                    $taggerUsage[$tagger] = array($type);
                }
                else {
                    $taggerUsage[$tagger][] = $type;
                }
            }
        }
        $versionFile = $this->testSuitePath.'/TermTaggerServerVersion.htm';
        if(!file_exists($versionFile)) {
            echo '<p style="color:red;">No TermTagger Version locally given for comparsion with running termtaggers!</p>';
        }
        echo '<ul>';
        $versionToCheck = file_get_contents($versionFile);
        $success = true;
        foreach($taggerUsage as $tagger => $usage) {
            $success = $this->checkOneTagger($termtaggers, $tagger, $usage, $versionToCheck) && $success;
        }
        if($success) {
            $this->xmlResultSummary['All TermTaggers are running in the correct version!'] = 'green';
        }
        else {
            $this->xmlResultSummary['Errors in TermTagger run and version check!'] = 'red';
        }
        echo '</ul>';
    }
    
    /**
     * convert termtagger version output in a printable form
     * @param string $output
     * @return string
     */
    protected function sanitizeTermTaggerOutput($output){
        $output = preg_replace('/^.*?<b>/', '<b style="">', $output); //empty style prevents first br addition
        return str_replace('<b>', '<br /><b>', strip_tags($output, '<b></b>'));
    }
    
    /**
     * checks version and runstate of one termtagger
     * @param stdClass $termtaggers
     * @param string $tagger
     * @param array $usage
     * @param string $versionToCheck
     * @return boolean
     */
    protected function checkOneTagger($termtaggers, $tagger, $usage, $versionToCheck) {
        $running = isset($termtaggers->running[$tagger]) && $termtaggers->running[$tagger];
        $version = $versionToCheck === $termtaggers->version[$tagger];
        $color = (($running && $version) ? 'green' : 'red');
        echo '<li style="color:'.$color.';">'.$tagger.' configured as '.join(';', $usage);
        if(!$running) {
            echo ' <b>is NOT running</b>.</li>';
            return false; //no version check needed afterwards
        }
        echo ' is running ';
        if($version){
            echo ' and version is OK.</li>';
            return true;
        }
        echo ' and <b>version is NOT OK</b>.';
        echo ' <h3>Version Received:</h3> '.$this->sanitizeTermTaggerOutput($termtaggers->version[$tagger]);
        echo ' <h3>Version Expected:</h3> '.$this->sanitizeTermTaggerOutput($versionToCheck);
        echo '</li>';
        return false;
    }
    
    protected function loopThroughTestXmlFiles($filter = null) {
        while ($file = $this->getNextTestXml()) {
            if(!empty($filter) && strpos(basename($file), $filter) === false) {
                continue;
            }
            
            $resultPreparation = $this->runTests($file,  $this->testcasePreparation);
            //runTests seams to kill the output from before, so we have to catch it and output it here!
            echo $this->firstResults;
            $this->firstResults = '';
            
            if(!$resultPreparation->wasSuccessful()){
                $this->echoResults($resultPreparation,$file);
                echo '<p style="color:red"><b>There have been errors in Test-Preperation. Main test not started.</b></p>';
                $this->xmlResultSummary[$file->getFilename()]='red';
                continue;
            }
            
            $result = $this->runTests($file,  $this->testcase);
            $this->echoResults($result,$file);
            $this->xmlResultSummary[$file->getFilename()]=  $this->errorColor;
        }
    }
    
    protected function echoResultSummary() {
        ksort($this->xmlResultSummary, SORT_NATURAL);
        echo "<h2>Summary of Results</h2>";
        foreach ($this->xmlResultSummary as $filename => $color) {
            echo '<a href="#'.$filename.'"><b style="color: '.$color.'">'.$filename.'</b></a><br>';
        }
    }
    
    /**
     * @param PHPUnit\Framework\TestResult $result
     * @return boolean
     */
    protected function echoResults(PHPUnit\Framework\TestResult $result,\SplFileInfo $file) {
        echo '<a name="'.$file->getFilename().'"><h2>'.$file->getFilename()."/ ".$this->testcase->getProperty('name')->getValue()."</h2></a>";
        echo "<h3>Description: ".$this->testcase->getProperty('description')->getValue()."</h3>";
            
        /* @var $result PHPUnit\Framework\TestResult */
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
                /* @var $failure PHPUnit\Framework\TestFailure */
                echo '<li>'.$error->exceptionMessage().'</li>';
            }
            echo "</ol>";
            return false;
        }

        
        if($failureCount>0){
            echo '<p style="color:'.$this->errorColor.'">Main Test failed.</p><ol style="color:'.$this->errorColor.'">';
            foreach ($failures as $key => $failure ) {
                /* @var $failure PHPUnit\Framework\TestFailure */
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
        $testsuite = new PHPUnit\Framework\TestSuite($class);

        /* @var $testsuite PHPUnit\Framework\TestSuite */
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

