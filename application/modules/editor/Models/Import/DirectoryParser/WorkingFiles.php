<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
   * - transit-fileextension does not exist in real life, since transit has none.
   *   It is added by editor_Transit_PluginBootstrap
   * @var array
   */
  protected $_importExtensionList;
  
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
  
  public function __construct() {
      $this->_importExtensionList = array_keys(editor_Models_Import_FileParser::getAllFileParsersMap());
  }
  
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