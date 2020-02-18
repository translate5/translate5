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
 * Foldertree Object Instanz wie in der Applikation benötigt
 */
class editor_Models_Foldertree extends ZfExtended_Models_Entity_Abstract {
    
    const TYPE_DIR = 'dir';
    const TYPE_FILE = 'file';
    
  protected $dbInstanceClass = 'editor_Models_Db_Foldertree';
  
  protected $objectTree = null;
  
  /**
   * @var array array(id => 'filePath',...)
   */
  protected $_paths = array();
  /**
   * @var string prefix of the filepath, i. e. review/ or relais/
   */
  protected $_pathPrefix = NULL;
  
  public function __construct() {
      parent::__construct();
      $config = Zend_Registry::get('config');
      $this->setPathPrefix($config->runtimeOptions->import->proofReadDirectory);
  }
  /**
   * @param string $prefix
   */
  public function setPathPrefix(string $prefix) {
      $this->_pathPrefix = $prefix;
  }
  /**
   * loads a Foldertree to the given TaskGuid
   * @param string $taskGuid
   * @return Zend_Db_Table_Row_Abstract
   */
  public function loadByTaskGuid(string $taskGuid) {
    return $this->row = $this->loadRow('taskGuid = ?', $taskGuid);
  }
  
  /**
   * gets the tree and decodes it interally from JSON
   * @return array
   */
  public function getTree() {
    if(is_null($this->objectTree)){
      $this->objectTree = json_decode($this->get('tree'));
    }
    return $this->objectTree;
  }
  
  /**
   * returns directly the JSON Repräsentation
   * @return string
   */
  public function getTreeAsJson() {
    return $this->get('tree');
  }
  
  /**
   * returns directly the JSON Repräsentation
   * @return string
   */
  public function getReferenceTreeAsJson() {
    return $this->get('referenceFileTree');
  }
  
  /**
   * stores the tree and converts it internally to JSON
   * @param array $tree
   */
  public function setTree(array $tree) {
    $this->objectTree = $tree;
    $this->set('tree', json_encode($tree));
  }
  
  /**
   * stores the reference file tree and converts it internally to JSON
   * @param array $tree
   */
  public function setReferenceFileTree(array $tree) {
    $this->set('referenceFileTree', json_encode($tree));
  }
  
  /**
   * syncs the Tree Data to Files. 
   */
  public function syncTreeToFiles(){
    $sync = ZfExtended_Factory::get('editor_Models_Foldertree_SyncToFiles', array($this));
    /* @var $sync editor_Models_Foldertree_SyncToFiles */
    $sync->recursiveSync();
  }
  /**
   * liest die Dateipfade und ids aus dem Foldertree-Json-Objekt aus
   *
   * @param string $taskGuid
   * @param string $type Erlaubte Werte: dir|file
   * @return array array(id => 'filePath',...)
   *        Die Pfade sind in einer Reihenfolge, so dass in der Hierarchie höhere Verzeichnisse
   *        niedrigere Array-Indizes haben
   *        id ist für Verzeichnisse und Dateien eindeutig
   */
  public function getPaths(string $taskGuid, string $type){
      $this->_paths = array();
      if($type !== self::TYPE_DIR and $type !== self::TYPE_FILE){
          throw new Zend_Exception('$type hatte den nicht erlaubten Wert '.$type);
      }
      $this->loadByTaskGuid($taskGuid);
      $this->getTree();
      $nodeVisitor = 'get'.  ucfirst($type).'PathsNodeVisitor';
      $this->$nodeVisitor($this->objectTree);
      return $this->_paths;
  }
  
  /**
   * konvertiert einen Objektbaum mit Pfadangaben zu einer Liste mit den Pfaden
   * @param stdClass $tree
   * @return array
   */
  public function getFilePathsByTree($tree){
      $this->getFilePathsNodeVisitor($tree);
      return $this->_paths;
  }
  
  /**
   * besucht jedes Kind im Baum und speichert dessen Filepath in $this->_paths
   * - Verzeichnisnamen werden übersprungen
   * @param array $children
   */
  protected function getFilePathsNodeVisitor(array $children,string $path = NULL) {
    foreach($children as $index => $child){
      if(! isset($child->isFile) || ! $child->isFile) {
        if(!empty($child->children)){
          $this->getFilePathsNodeVisitor($child->children,$path.$child->filename.DIRECTORY_SEPARATOR);
        }
        continue;
      }
      $this->handleFile($child, $path);
    }
  }
  
  /**
   * Wird im getFilePathsNodeVisitor für jede Datei aufgerufen 
   * @param stdClass $child
   * @param string $path
   */
  protected function handleFile(stdClass $child, $path) {
      $path = $this->_pathPrefix !==''? $this->_pathPrefix.DIRECTORY_SEPARATOR.$path:$path;
      $this->_paths[$child->id] = $path.$child->filename;      
  }
  
  /**
   * besucht jedes Kind im Baum und speichert dessen Dirpath in $this->_paths
   * - Dateinamen werden übersprungen
   * @param array $children
   */
  protected function getDirPathsNodeVisitor(array $children,string $path = NULL) {
    foreach($children as $index => $child){
        if($child->cls !== 'folder') {
            continue;
        }
        if(!empty($child->children)){
          $this->getDirPathsNodeVisitor($child->children,$path.$child->filename.DIRECTORY_SEPARATOR);
        }
        $this->_paths[$child->id] = $path.$child->filename;
    }
  }
}