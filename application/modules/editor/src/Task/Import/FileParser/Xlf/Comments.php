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

namespace MittagQI\Translate5\Task\Import\FileParser\Xlf;

use editor_Models_Comment;
use editor_Models_ConfigException;
use editor_Models_Segment;
use editor_Models_Task;
use ReflectionException;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * encapsulates the XLF comment import
 */
class Comments
{
    public const NOTE_USERGUID = 'xlf-note-imported';

    protected const ANNOTATE_SOURCE = 'source';

    protected const ANNOTATE_TARGET = 'target';

    /**
     * @var editor_Models_Comment[]|null[]
     */
    private array $comments = [];

    /**
     * @var editor_Models_Comment[]
     */
    private array $resnameComments = [];

    public function __construct(
        private editor_Models_Task $task,
    ) {
    }

    /**
     * Imports the comments of last processed segment
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    public function importComments(int $segmentId): void
    {
        $comment = null;

        foreach ($this->resnameComments as $comment) {
            $comment->setTaskGuid($this->task->getTaskGuid());
            $comment->setSegmentId($segmentId);
            $comment->save();
        }

        //reset internal resname collector
        $this->resnameComments = [];

        $importComments = (bool) ($this->task->getConfig()->runtimeOptions->import->xliff->importComments ?? false);
        if ($importComments && ! empty($this->comments)) {
            foreach ($this->comments as $comment) {
                $comment->setTaskGuid($this->task->getTaskGuid());
                $comment->setSegmentId($segmentId);
                $comment->save();
            }

            //reset internal collector
            $this->comments = [];
        }

        //if there was at least one processed comment, we have to sync the comment contents to the segment
        if (! is_null($comment)) {
            $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
            $segment->load($segmentId);
            $comment->updateSegment($segment, $this->task->getTaskGuid());
        }
    }

    /**
     * @param array{lang: string, from: ?string, priority: string, annotates: string} $attributes
     * @throws ReflectionException
     */
    public function addByNote(string $commentText, array $attributes): void
    {
        $this->comments[] = $comment = ZfExtended_Factory::get(editor_Models_Comment::class);

        $commentText = $this->addCommentMeta($attributes, $commentText);

        $comment->setComment($commentText);

        $comment->setUserGuid(self::NOTE_USERGUID);
        $comment->setUserName($attributes['from'] ?? 'no user');

        $comment->setCreated(NOW_ISO);
        $comment->setModified(NOW_ISO);
    }

    public function addResnameComment(string $resname): void
    {
        $comment = new editor_Models_Comment();
        $comment->setComment('resname: ' . $resname);
        $comment->setUserGuid(self::NOTE_USERGUID);
        $comment->setUserName('no user');
        $comment->setCreated(NOW_ISO);
        $comment->setModified(NOW_ISO);

        $this->resnameComments[] = $comment;
    }

    private function addCommentMeta(array $attributes, string $commentText): string
    {
        $metaData = [];
        switch ($attributes['annotates']) {
            case self::ANNOTATE_SOURCE:
                $metaData[] = 'annotates source column';

                break;
            case self::ANNOTATE_TARGET:
                $metaData[] = 'annotates target column';

                break;
            default:
                break;
        }

        if (! is_null($attributes['priority'])) {
            $metaData[] = 'priority: ' . $attributes['priority'];
        }
        if (! empty($metaData)) {
            $commentText = join('; ', $metaData) . "\n" . $commentText;
        }

        return $commentText;
    }

    public function add(editor_Models_Comment $comment): void
    {
        $this->comments[] = $comment;
    }
}
