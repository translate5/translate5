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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * XLF Fileparser Add On abstract class
 */
abstract class editor_Models_Export_FileParser_Xlf_Namespaces_Abstract {
    /**
     * container for exported comments per segment
     * @var array
     */
    protected $comments = [];

    /**
     * Gives the Namespace class the ability to add custom handlers to the xmlparser
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser){
        //method stub
    }

    /**
     * Loads and adds the the comments of the current segment placeholder into $this->comments
     * @param array $attributes
     * @param editor_Models_Import_FileParser_XmlParser $xmlparser
     * @param editor_Models_Task $task
     * @throws Zend_Exception
     */
    protected function loadComments(array $attributes, editor_Models_Import_FileParser_XmlParser $xmlparser, editor_Models_Task $task) {
        if(empty($attributes['id']) && $attributes['id'] !== '0') {
            throw new Zend_Exception('Missing id attribute in '.$xmlparser->current()['openerKey']);
        }
        $comment = ZfExtended_Factory::get('editor_Models_Comment');
        /* @var $comment editor_Models_Comment */
        $commentForSegment = $comment->loadBySegmentAndTaskPlain((int) $attributes['id'], $task->getTaskGuid());
        if(empty($commentForSegment)) {
            return;
        }
        $this->comments = array_merge($commentForSegment, $this->comments);
    }
}
