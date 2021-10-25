<?php
 /*
   START LICENSE AND COPYRIGHT

  This file is part of the ZfExtended library and build on Zend Framework

  Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

  This file is multi-licensed under the EPL, LGPL and GPL licenses. 

  It may be used under the terms of the Eclipse Public License - v 1.0
  as published by the Eclipse Foundation and appearing in the file eclipse-license.txt 
  included in the packaging of this file.  Please review the following information 
  to ensure the Eclipse Public License - v 1.0 requirements will be met:
  http://www.eclipse.org/legal/epl-v10.html.

  Also it may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
  or the GNU GENERAL PUBLIC LICENSE version 3 as published by the 
  Free Software Foundation, Inc. and appearing in the files lgpl-license.txt and gpl3-license.txt
  included in the packaging of this file.  Please review the following information 
  to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3 requirements or the
  GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
  http://www.gnu.org/licenses/lgpl.html   /  http://www.gnu.org/licenses/gpl.html
  
  @copyright  Marc Mittag, MittagQI - Quality Informatics
  @author     MittagQI - Quality Informatics
  @license    Multi-licensed under Eclipse Public License - v 1.0 http://www.eclipse.org/legal/epl-v10.html, GNU LESSER GENERAL PUBLIC LICENSE version 3 http://www.gnu.org/licenses/lgpl.html and GNU GENERAL PUBLIC LICENSE version 3 http://www.gnu.org/licenses/gpl.html

  END LICENSE AND COPYRIGHT 
 */

abstract class erp_Models_Comment_Abstract extends ZfExtended_Models_Entity_Abstract {
    
    /**
     * renders a list of comments
     * @param array $comment
     * @return string
     */
    protected function getMarkedUp(array $comments) {
        $markup = '';
        $commentsMarkup = array();
    
        $view = clone Zend_Layout::getMvcInstance()->getView();
    
        foreach($comments as $comment) {
            $view->comment = $comment;
            $commentsMarkup[] = $view->render("comment.phtml");
        }
        $markup = join("\n", $commentsMarkup);
    
        return $markup;
    }
    
    /**
     * calculates the isEditable state of the current comment
     */
    public function isEditable() {
        $sessionUser = new Zend_Session_Namespace('user');
        //SELECT explained: 
        //if there are newer comments (id > ?) to this segment (segId=?) of another user (userGuid=?), the actual user cant edit the comment anymore
        $s = $this->db->select()
        ->where('id > ?', $this->getId())
        ->where('userId != ?', $sessionUser->data->id);
        $res = $this->db->getAdapter()->fetchAll($this->whereForeignId($s));
        return empty($res);
    }
    
    /**
     * adds the foreign Id filter of the specific type
     * @param Zend_Db_Select $select
     * @return Zend_Db_Select
     */
    abstract protected function whereForeignId(Zend_Db_Select $select);
    
    /**
     * returns the ID of the foreign associated entity
     */
    abstract public function getForeignId();
    
    
    /**
     * loads all comments to the given foreign Id
     * @param integer $id
     * @return array
     */
    public function loadByForeignId($id) {
        $s = $this->whereForeignId($this->db->select());
        $s->order('id DESC');
        
        return $this->addEditableFlag($this->db->getAdapter()->fetchAll($s));
    }
    
    /**
     * overriden to inject isEditable flag
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::loadAll()
     */
    public function loadAll() {
        $s = $this->db->select();
        $this->filter->setSort('');
        $s->order('id DESC');
        return $this->addEditableFlag($this->loadFilterdCustom($s));
    }
    
    /**
     * calculates and adds the isEditable flag to comments
     * @param array $comments
     * @return unknown
     */
    protected function addEditableFlag(array $comments) {
        $sessionUser = new Zend_Session_Namespace('user');
        $editable = true;
        //comment is editable until an other user has edited it. 
        //the following algorithm depends on correct sort (id DESC => from newest to oldest comment)
        foreach($comments as &$comment) {
            $comment['isEditable'] = ($editable && $comment['userId'] == $sessionUser->data->id);
            $editable = $editable && $comment['isEditable'];
        }
        return $comments;
    }
}