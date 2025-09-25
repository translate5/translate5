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

use MittagQI\Translate5\Segment\Tag\SegmentTagSequence;

/**
 * Abstraction to bundle the segment's text and it's internal tags to an OOP accessible structure
 * The structure of the tags in this class is a simple sequence, any nesting / interleaving is covered with rendering / unparsing
 * @method editor_Segment_FieldTags cloneFiltered(array $includedTypes = null, bool $finalize = true)
 * @method editor_Segment_FieldTags cloneWithoutTrackChanges(array $includedTypes = null, bool $condenseBlanks = true)
 */
class editor_Segment_FieldTags extends SegmentTagSequence
{
    protected static string $logger_domain = 'editor.fieldtags';

    /**
     * The counterpart to ::toJson: creates the tags from the serialized JSON data
     * @throws Exception
     */
    public static function fromJson(editor_Models_Task $task, string $jsonString): editor_Segment_FieldTags
    {
        try {
            return static::fromJsonData($task, json_decode($jsonString));
        } catch (Throwable) {
            throw new Exception('Could not deserialize editor_Segment_FieldTags from JSON ' . $jsonString);
        }
    }

    /**
     * Creates the tags from deserialized JSON data
     * @throws Exception
     */
    public static function fromJsonData(editor_Models_Task $task, stdClass $data): editor_Segment_FieldTags
    {
        try {
            $tags = new editor_Segment_FieldTags($task, $data->segmentId, $data->text, $data->field, $data->dataField, $data->saveTo, $data->ttName);
            $creator = editor_Segment_TagCreator::instance();
            foreach ($data->tags as $tag) {
                $segmentTag = $creator->fromJsonData($tag);
                $tags->addTag($segmentTag, $segmentTag->order, $segmentTag->parentOrder);
            }
            // crucial: we do not serialize the deleted/inserted props as they're serialized indirectly with the trackchanges tags
            // so we have to re-evaluate these props now
            $tags->evaluateDeletedInserted();

            return $tags;
        } catch (Throwable) {
            throw new Exception('Could not deserialize editor_Segment_FieldTags from deserialized JSON-Data ' . json_encode($data));
        }
    }

    /**
     * The task the segments belong to
     */
    private editor_Models_Task $task;

    /**
     * The id of the segment we refer to
     */
    private int $segmentId;

    /**
     * The field our fieldtext comes from e.g. 'source', 'target'
     */
    private string $field;

    /**
     * The data-index our fieldtext comes from e.g. 'targetEdit'
     */
    private string $dataField;

    /**
     * The field of the segment's data we will be saved to
     */
    private ?string $saveTo;

    /**
     * Special Helper to Track the field-name as used in the TermTagger Code
     * TODO: Check, if this is really neccessary
     */
    private ?string $ttName;

    public function __construct(editor_Models_Task $task, int $segmentId, ?string $text, string $field, string $dataField, string $additionalSaveTo = null, string $ttName = null)
    {
        $this->task = $task;
        $this->segmentId = $segmentId;
        $this->field = $field;
        $this->dataField = $dataField;
        $this->saveTo = $additionalSaveTo;
        $this->ttName = ($ttName === null) ? $field : $ttName;
        parent::__construct($text);
    }

    /**
     * @return number
     */
    public function getSegmentId(): int
    {
        return $this->segmentId;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getTask(): editor_Models_Task
    {
        return $this->task;
    }

    /**
     * Retrieves the field's data index as defined by editor_Models_SegmentFieldManager::getDataLocationByKey
     */
    public function getDataField(): string
    {
        return $this->dataField;
    }

    /**
     * Evaluates, if the bound field is a source field
     */
    public function isSourceField(): bool
    {
        return str_starts_with($this->field, 'source');
    }

    /**
     * Evaluates, if the bound field is a target field
     */
    public function isTargetField(): bool
    {
        return ! $this->isSourceField();
    }

    /**
     * TODO: might be unneccessary
     */
    public function getTermtaggerName(): string
    {
        return $this->ttName;
    }

    /**
     * Called after the unparsing Phase to finalize a single tag
     */
    protected function finalizeAddTag(editor_Segment_Tag $tag): void
    {
        $tag->field = $this->field; // we transfer our field to the tag for easier handling of our segment-tags
    }

    /**
     * @return string[]
     */
    public function getSaveToFields(): array
    {
        $fields = [$this->dataField];
        if (! empty($this->saveTo)) {
            $fields[] = $this->saveTo;
        }

        return $fields;
    }

    /**
     * TermTagging may introduce errors with wrong nesting of tags
     * This API is meant to fix that
     */
    public function fixTermTaggerTags(): void
    {
        $fixer = new \MittagQI\Translate5\Segment\TagRepair\TermTaggerTagsFixer($this);
        if ($fixer->needsFix()) {
            $this->tags = $fixer->getFixedTags();
            if ($fixer->hasWarnings()) {
                $warningData = [
                    'task' => $this->task,
                    'text' => $this->getText(),
                    'details' => $fixer->getWarnings(),
                    'originalMarkup' => $this->getOriginalMarkup(),
                ];
                // we create only an info instead of a warning here as there is currently nothing, the user can do
                $this->createLogger()->info('E1696', 'Termtagger created invalid markup: {details}', $warningData);
            }
        }
    }

    /* Serialization API */

    public function jsonSerialize(): stdClass
    {
        $data = parent::jsonSerialize();
        $data->segmentId = $this->segmentId;
        $data->field = $this->field;
        $data->dataField = $this->dataField;
        $data->saveTo = $this->saveTo;
        $data->ttName = $this->ttName;

        return $data;
    }

    /* Unparsing API */

    /**
     * Called after the unparsing phase to finalize all tags
     */
    protected function finalizeUnparse(): void
    {
        parent::finalizeUnparse();
        $num = count($this->tags);
        for ($i = 0; $i < $num; $i++) {
            $this->tags[$i]->field = $this->field;
            $this->tags[$i]->finalize($this, $this->task);
        }
    }

    /* Cloning API */

    protected function createClone(): self
    {
        return new self($this->task, $this->segmentId, $this->text, $this->field, $this->dataField, $this->saveTo, $this->ttName);
    }

    /* Logging API */

    protected function addErrorDetails(array &$errorData): void
    {
        $errorData['text'] = $this->text;
        $errorData['segmentId'] = $this->segmentId;
        $errorData['taskId'] = $this->task->getId();
        $errorData['taskGuid'] = $this->task->getTaskGuid();
        $errorData['taskName'] = $this->task->getTaskName();
    }

    /* Debugging API */

    /**
     * Debug state of our segment props
     */
    public function debugProps(): string
    {
        return '[ segment:' . $this->segmentId . ' | field:' . $this->field . ' | dataField:' . $this->dataField . ' | saveTo:' . $this->saveTo . ' ]';
    }
}
