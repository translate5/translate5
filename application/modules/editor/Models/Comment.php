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
 * Comment Entity Object
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method integer getSegmentId() getSegmentId()
 * @method void setSegmentId() setSegmentId(integer $segmentId)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $username)
 * @method string getComment() getComment()
 * @method void setComment() setComment(string $comment)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $created)
 * @method string getModified() getModified()
 * @method void setModified() setModified(string $modified)
 */
class editor_Models_Comment extends ZfExtended_Models_Entity_Abstract {
  protected $dbInstanceClass = 'editor_Models_Db_Comments';
  protected $validatorInstanceClass = 'editor_Models_Validator_Comment';

  /**
   * renders a single comment
   * @param Zend_View $view
   * @param array $comment
   * @return string
   */
  protected function getMarkedUp(Zend_View $view, array $comment) {
      $view->comment = $comment;
      return $view->render("comment.phtml");
  }
  
  /**
   * updates the segments comments field by merging all comments to the segment, and apply HTML markup to each comment
   * @param integer $segmentId
   * @param string $taskGuid
   */
  public function updateSegment(integer $segmentId, string $taskGuid) {
      $comments = $this->loadBySegmentId($segmentId, $taskGuid);
      $commentsMarkup = array();
      $view = clone Zend_Layout::getMvcInstance()->getView();
      foreach($comments as $comment) {
          $commentsMarkup[] = $this->getMarkedUp($view, $comment);
      }
      $segment = ZfExtended_Factory::get('editor_Models_Segment');
      /* @var $segment editor_Models_Segment */
      $segment->load($segmentId);
      
      if($taskGuid === $segment->get('taskGuid')) {
          $segment->set('comments', join("\n", $commentsMarkup));
          $segment->save();
      }
  }
  
  /**
   * loads all comments to the given segmentId, filter also only the session loaded taskguid
   * sorts from newest to oldest comment, does compute the isEditable flag
   * @param integer $segmentId
   * @param string $taskGuid
   * @return array
   */
  public function loadBySegmentId(integer $segmentId, string $taskGuid) {
      $sessionUser = new Zend_Session_Namespace('user');
      $userGuid = isset($sessionUser->data->userGuid) ? $sessionUser->data->userGuid : null;
      $comments = $this->loadBySegmentAndTaskPlain($segmentId, $taskGuid);
      $editable = true;
      //comment is editable until an other user has edited it. 
      //the following algorithm depends on correct sort (id DESC => from newest to oldest comment)
      foreach($comments as &$comment) {
          $comment['isEditable'] = ($editable && $userGuid && $comment['userGuid'] == $userGuid);
          $editable = $editable && $comment['isEditable'];
      }
      return $comments;
  }
  
  /**
   * Loads all comments of a segment by segmentid and taskguid, orders by creation order
   * does not provide isEditable info
   */
  public function loadBySegmentAndTaskPlain(integer $segmentId, string $taskGuid) {
      $s = $this->db->select()
      ->where('taskGuid = ?', $taskGuid)
      ->where('segmentId = ?', $segmentId)
      ->order('id DESC');
      return $this->db->getAdapter()->fetchAll($s);
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
      ->where('segmentId = ?', $this->getSegmentId())
      ->where('userGuid != ?', $sessionUser->data->userGuid);
      $res = $this->db->getAdapter()->fetchAll($s);
      return empty($res);
  }
}