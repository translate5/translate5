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
 * Comment Entity Object
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method integer getSegmentId() getSegmentId()
 * @method void setSegmentId() setSegmentId(int $segmentId)
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
    
  /**
   * Used to identify a Comment in the Frontend /comment overview) and distinguish it from an Annotation
   * @var string
   */
  const FRONTEND_ID = 'segmentComment';
  
  protected $dbInstanceClass = 'editor_Models_Db_Comments';
  protected $validatorInstanceClass = 'editor_Models_Validator_Comment';

  /**
   * @var editor_Models_Comment_Meta
   */
  protected $meta;
  
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
   * anonymizes a comment that has been markedUp using comment.phtml 
   * (= replace author given in <span class="author">xyz</span>)
   * @param string $text
   */
  public function renderAnonymizedComment (string $text) {
      // Details regarding the author ("User1", "User2", ...) are available in the segment's context-column,
      // hence here we skip turning the given data to "User1" etc.
      $text = preg_replace('/<span class="author">.*?<\/span>/', '<span class="author"></span>', $text);
      return $text;
  }
  
  /**
   * updates the segments comments field by merging all comments to the segment, and apply HTML markup to each comment
   * @param editor_Models_Segment $segment
   * @param string $taskGuid
   */
  public function updateSegment(editor_Models_Segment $segment, string $taskGuid) {
      $comments = $this->loadBySegmentId($segment->getId(), $taskGuid);
      $commentsMarkup = array();
      $view = clone Zend_Layout::getMvcInstance()->getView();
      foreach($comments as $comment) {
          $commentsMarkup[] = $this->getMarkedUp($view, $comment);
      }
      if($taskGuid === $segment->get('taskGuid')) {
          $segment->set('comments', join("\n", $commentsMarkup));
          $segment->save();
      }
  }

  /**
   * loads all comments to the given segmentId, filter also only the session loaded taskguid
   * sorts from newest to oldest comment, does compute the isEditable flag
   * @param int $segmentId
   * @param string $taskGuid
   * @return array
   */
  public function loadBySegmentId(int $segmentId, string $taskGuid) {
      $sessionUser = new Zend_Session_Namespace('user');
      $userGuid = $sessionUser->data->userGuid ?? null;
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
   * Loads all comments of a task by taskguid with corresponding page, optionally only the one specified by id
   * does not provide isEditable info
   * @param string $taskGuid
   * @param string $cid (optional) - commentId if only one comment
   * @return array
   */
  public function loadByTaskPlain(string $taskGuid, string $cid='') {
        $s = $this->db->select()
            ->distinct()
            ->setIntegrityCheck(false)
            ->from(['comments' => $this->db->info($this->db::NAME)])
            ->where('comments.taskGuid = ?', $taskGuid) // sort in frontend, see there
            ->joinLeft(
                    ['segments' => 'LEK_segments'],
                    'comments.segmentId = segments.id',
                    ['segmentNrInTask' => 'segments.segmentNrInTask']
                );
        if($cid) {
            $s->where('comments.id = ?', $cid);
            $row = $this->db->getAdapter()->fetchRow($s);
            return ($row) ? [ $row ] : [];
        } else {
            return $this->db->getAdapter()->fetchAll($s);
        }
  }
  /**
   * Loads all comments of a segment by segmentid and taskguid, orders by creation order
   * does not provide isEditable info
   * @param int $segmentId
   * @param string $taskGuid
   * @return array|null
   */
  public function loadBySegmentAndTaskPlain(int $segmentId, string $taskGuid) {
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
  
  /**
   * convenient method to get the comment meta data
   * @return editor_Models_Comment_Meta
   */
  public function meta() {
      if(empty($this->meta)) {
          $this->meta = ZfExtended_Factory::get('editor_Models_Comment_Meta');
      }
      elseif($this->getId() == $this->meta->getCommentId()) {
          return $this->meta;
      }
      try {
          $this->meta->loadByCommentId($this->getId());
      } catch (ZfExtended_Models_Entity_NotFoundException $e) {
          $this->meta->init([
              'taskGuid' => $this->getTaskGuid(), 
              'commentId' => $this->getId(),
          ]);
      }
      return $this->meta;
  }
}