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
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Export\FileParser\Xlf;

use editor_Models_Comment;
use editor_Models_Task;
use ReflectionException;
use XMLWriter;
use ZfExtended_Factory;
use ZfExtended_Utils;

/**
 * encapsulates the XLF comment export
 */
class Comments
{
    /**
     * shared xmlWriter instance (between multiple exported files)
     */
    protected static ?XMLWriter $xmlWriter = null;
    /**
     * @var array
     */
    private array $comments = [];
    private int $translate5CommentCount = 0;

    public function __construct(protected bool $enabled = true, protected bool $addTranslate5Attributes = true)
    {
        if (is_null(self::$xmlWriter)) {
            self::$xmlWriter = new XMLWriter();
            self::$xmlWriter->openMemory();
        }
    }

    public function getTranslate5CommentCount(): int
    {
        return $this->translate5CommentCount;
    }

    /**
     * Loads and adds the comments of the current segment placeholder into $this->comments
     * @param array $attributes
     * @param editor_Models_Task $task
     * @throws ReflectionException
     */
    public function loadComments(array $attributes, editor_Models_Task $task): void
    {
        if (!$this->isEnabled() || (empty($attributes['ids']) && $attributes['ids'] !== '0')) {
            // there may be no ID if the trans-unit contains only not importable (tags only) segments.
            // In that case just do nothing.
            return;
        }
        $ids = explode(',', $attributes['ids']);
        $comment = ZfExtended_Factory::get(editor_Models_Comment::class);
        foreach ($ids as $id) {
            $commentForSegment = $comment->loadBySegmentAndTaskPlain((int)$id, $task->getTaskGuid());
            if (!empty($commentForSegment)) {
                $this->comments = array_merge($commentForSegment, $this->comments);
            }
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getCommentXml(callable $writer = null): string
    {
        if (!$this->isEnabled() && empty($this->comments)) {
            return '';
        }

        foreach ($this->comments as $comment) {
            if (is_null($writer)) {
                $this->xlfNoteWriter($comment);
            } else {
                $writer(self::$xmlWriter, $comment);
            }
        }
        $this->comments = [];
        return self::$xmlWriter->flush();
    }

    protected function xlfNoteWriter(array $comment): void
    {
        if ($comment['userGuid'] == \MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments::NOTE_USERGUID) {
            return;
        }

        self::$xmlWriter->startElement('note');
        self::$xmlWriter->writeAttribute('from', $comment['userName']);

        //this can not be done in the Translate5 namespace class, since the namespace is added on demand
        if ($this->addTranslate5Attributes) {
            $this->translate5CommentCount++;
            self::$xmlWriter->writeAttribute(
                'translate5:time',
                gmdate('Y-m-d\TH:i:s\Z', strtotime($comment['modified']))
            );
            self::$xmlWriter->writeAttribute('translate5:userGuid', $comment['userGuid']);
        }

        self::$xmlWriter->writeAttribute('annotates', 'general');
        self::$xmlWriter->text($comment['comment']);
        self::$xmlWriter->endElement();
    }
}
