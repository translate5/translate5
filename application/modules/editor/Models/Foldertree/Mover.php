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
 * Foldertree Helper Klasse zum Verschieben von Nodes im Tree
 */
class editor_Models_Foldertree_Mover {
  /**
   * @var editor_Models_Foldertree
   */
  protected $actualTree;
  
  /**
   * object Tree of loaded Foldertree
   * @var array
   */
  protected $objectTree;
  
  /**
   * maps the id to the Object, for faster processing
   * @var array
   */
  protected $idMap;
  
  /**
   * stores the index of a item in the parent children array 
   * @var array
   */
  protected $nodeIndex = array();
  
  /**
   * needs the actual Foldertree to move the Files in
   * @param Models_Foldertree $actualTree
   */
  public function __construct(editor_Models_Foldertree $actualTree){
    $this->actualTree = $actualTree;
    $this->createObjectTreeAndMap();
  }
  
  /**
   * creates $this->idMap and $this->objectMap out of the string representation $this->actualTree->tree
   */
  protected function createObjectTreeAndMap() {
    $this->objectTree = $this->actualTree->getTree();
    $this->nodeVisitor($this->objectTree);
  }
  
  /**
   * visits every child in the tree, and adds the data to $this->idMap
   * @param array $children
   */
  protected function nodeVisitor(array $children) {
    foreach($children as $index => $child){
      $this->idMap[$child->id] = $child;
      $this->nodeIndex[$child->id] = $index;
      if(!empty($child->children)){
        $this->nodeVisitor($child->children);
      }
    }
  }
  
  /**
   * returns the File Node with the given ID
   * @param integer $id
   * @return stdClass
   */
  public function getById($id) {
    return $this->idMap[$id];
  }
  
  /**
   * moves the File/Directory with the given id to the given parentid and index
   * @param integer $id
   * @param integer $parentId
   * @param integer $index
   */
  public function moveNode($id, $parentId, $index) {
    $this->removeFromOldParent($id);
    $this->insertIntoNewParent($id, $parentId, $index);
    $this->actualTree->setTree($this->objectTree);
    $this->actualTree->save();
  }
  
  protected function removeFromOldParent($id) {
    $oldIndex = $this->nodeIndex[$id];
    $oldParentid = $this->idMap[$id]->parentId;
    if($oldParentid == 0){
      unset($this->objectTree[$oldIndex]);
      //das folgende muss sein, da ansonsten json_encode ein Object anstatt einem Array draus macht
      $this->objectTree = array_values($this->objectTree);
    } else {      
      unset($this->idMap[$oldParentid]->children[$oldIndex]);
      //das folgende muss sein, da ansonsten json_encode ein Object anstatt einem Array draus macht
      $this->idMap[$oldParentid]->children = array_values($this->idMap[$oldParentid]->children);
    }
  }
  
  protected function insertIntoNewParent($id, $parentId, $index) {
    $newchildren = array();
    $i = 0;
    if($parentId == 0){
      $children = $this->objectTree;
    }
    else {
      $children = $this->idMap[$parentId]->children;
    }
    foreach($children as $child){
      if($index == $i++){
        $newchildren[] = $this->idMap[$id];
      }
      $newchildren[] = $child;
    }
    //Am Ende einfügen
    if($index >= $i){
      $newchildren[] = $this->idMap[$id];
    }
    if($parentId == 0){
      $this->objectTree = $newchildren;
    } else { 
      $this->idMap[$parentId]->children = $newchildren;
    }
    $this->idMap[$id]->parentId = $parentId;
  }
}