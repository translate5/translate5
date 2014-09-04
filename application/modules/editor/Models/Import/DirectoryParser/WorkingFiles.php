<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@+ 
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 * 
 */
/**
 * Klasse zum Parsen und Importieren von Dateistrukturen
 */
class editor_Models_Import_DirectoryParser_WorkingFiles {
  /**
   * Datei- oder Verzeichnisnamen in dieser Liste werden ignoriert. 100% match.
   * @var array
   */
  protected $ignoreList = array('.svn');
  /**
   * filenames with this extension will be imported (without leading dot)
   * - caseInsensitiv
   * - all others will be ignored
   * @var array
   */
  //protected $_importExtensionList = array('sdlxliff','csv');
  protected $_importExtensionList = array('sdlxliff','xlf','csv');
  
  /**
   * RootNode Container
   * @var StdClass
   */
  protected $rootNode;
  
  /**
   * contains DirectoryIterator directories for sorting
   * @var array
   */
  protected $directories = array();
  
  /**
   * contains DirectoryIterator files for sorting
   * @var array
   */
  protected $filenames = array();
  
  /**
   * parses the given directory and returns a Object tree ready for output as JSON
   * @param string $directoryPath
   * @return object Directory Object Tree
   */
  public function parse($directoryPath){
      $rootNode = $this->getInitialRootNode();
      $this->iterateThrough($rootNode, $directoryPath);
      return $rootNode->children;
  }
  
  protected function getInitialRootNode(){
      $rootNode = new stdClass();
      $rootNode->id = 0;
      $rootNode->children = array();
      $rootNode->path = '';
      $rootNode->filename = '';
      return $rootNode;
  }
  
  /**
   * iterates through the given DirectoryPath and processes the files and directories
   * @param StdClass $rootNode Root of the tree
   * @param string $directoryPath
   */
  protected function iterateThrough(StdClass $rootNode, string $directoryPath){
    $this->rootNode = $rootNode;
    $localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'LocalEncoded'
        );
    $iterator = new DirectoryIterator($directoryPath);
    /* @var $fileinfo DirectoryIterator */
    foreach ($iterator as $fileinfo) {
      if($this->isIgnored($fileinfo,$directoryPath)) {
        continue;
      }
      $fileName = $localEncoded->decode($fileinfo->getFilename());
      if($fileinfo->isFile()) {
        $this->filenames[$fileName] = $fileinfo->getPathname();
      }
      if($fileinfo->isDir()) {
        $this->directories[$fileName] = $fileinfo->getPathname();
      }
    }
    $this->sortItems();
    $this->buildRecursiveTree();
  }
  
  protected function sortItems() {
    ksort($this->filenames);
    ksort($this->directories);
  }
  
  protected function buildRecursiveTree() {
    foreach($this->directories as $directory => $path) {
      $this->rootNode->children[] = $this->getDirectoryNodeAndIterate($directory);
    }
    foreach($this->filenames as $filename => $filepath) {
      $this->rootNode->children[] = $this->getFileNode($filename);
    }
  }
  
  /**
   * checks if the given File/Directory should be ignored
   * @param DirectoryIterator $file
   * @return boolean 
   */
  protected function isIgnored(DirectoryIterator $file,string $directoryPath){
      if($file->isDot() || in_array($file->getFilename(), $this->ignoreList)){
          return true;
      }
      if(is_dir($directoryPath.DIRECTORY_SEPARATOR.$file)){
          return false;
      }
      if(empty($this->_importExtensionList)){
          return false; //no extension to filter => pass all files
      }
      foreach ( $this->_importExtensionList as $ext) {
          if(preg_match('"\.'.$ext.'$"i', $file)){
              return false;
          }
      }
      return true;
  }
  
  /**
   * Creates a FileNode out of given $file
   * @param string $filename
   * @return stdClass
   */
  protected function getFileNode($filename) {
    $node = new stdClass();
    $node->id = 0; // from save to DB
    $node->parentId = 0;//from first sync to files call;
    $node->cls = 'file';
    $node->isFile = true;
    $node->filename = $filename;
    $node->segmentid = 0;
    $node->segmentgridindex = 0;
    $node->path = $this->rootNode->path.$this->rootNode->filename.'/';
    return $node;
  }
  
  /**
   * Creates a DirectoryNode out of given Directory and iterate through it / build up the tree
   * @param string $directory
   * @return stdClass
   */
  protected function getDirectoryNodeAndIterate($directory) {
    $node = $this->getDirectoryNode($directory);
    $iteration = new static;
    $iteration->iterateThrough($node, $this->directories[$directory]);    
    return $node;
  }
  /**
   * Creates a DirectoryNode out of given Directory
   * @param string $directory
   * @return stdClass
   */
  protected function getDirectoryNode($directory) {
    $node = new stdClass();
    $node->id = 0;  // from save to DB
    $node->filename = $directory;
    $node->path = $this->rootNode->path.$this->rootNode->filename.'/';
    $node->cls = 'folder';
	$node->children = array();
    return $node;
  }
}