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

use PHPHtmlParser\Dom\Node\HtmlNode;

/**
 * Abstraction to bundle the segment's text and it's internal tags to an OOP accessible structure
 * The structure of the tags in this class is a simple sequence, any nesting / interleaving is covered with rendering / unparsing
 */
class editor_Segment_FieldTags extends editor_TagSequence
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        $this->_setMarkup($text);
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
     * Returns the field text (which covers the textual contents of internal tags as well !)
     */
    public function getFieldText(bool $stripTrackChanges = false, bool $condenseBlanks = true): string
    {
        if ($stripTrackChanges && (count($this->tags) > 0)) {
            return $this->getFieldTextWithoutTrackChanges($condenseBlanks);
        }

        return $this->text;
    }

    /**
     * Retrieves our field-text lines.
     * This means, that all TrackChanges Del Contents are removed and our fild-text is splitted by all existing Internal Newline tags
     * @return string[]
     */
    public function getFieldTextLines(bool $condenseBlanks = true): array
    {
        $clone = $this->cloneWithoutTrackChanges([editor_Segment_Tag::TYPE_INTERNAL], $condenseBlanks);
        $clone->replaceTagsForLines();

        return explode(editor_Segment_NewlineTag::RENDERED, $clone->render());
    }

    public function getFieldTextLength(bool $stripTrackChanges = false, bool $condenseBlanks = true): int
    {
        if ($stripTrackChanges && (count($this->tags) > 0)) {
            return mb_strlen($this->getFieldTextWithoutTrackChanges($condenseBlanks));
        }

        return $this->getTextLength();
    }

    public function isFieldTextEmpty(bool $stripTrackChanges = false, bool $condenseBlanks = true): bool
    {
        if ($stripTrackChanges && (count($this->tags) > 0)) {
            return ($this->getFieldTextLength(true, $condenseBlanks) == 0);
        }

        return ($this->getTextLength() === 0);
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
    protected function finalizeAddTag(editor_Segment_Tag $tag)
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
     * Retrieves the internal tags of a certain type
     * @return editor_Segment_Tag[]
     */
    public function getByType(string $type, bool $includeDeleted = false): array
    {
        $result = [];
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && ($includeDeleted || ! $tag->wasDeleted)) {
                $result[] = $tag;
            }
        }

        return $result;
    }

    /**
     * Removes the internal tags of a certain type
     */
    public function removeByType(string $type, bool $skipDeleted = false)
    {
        $result = [];
        $replace = false;
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && (! $skipDeleted || ! $tag->wasDeleted)) {
                $replace = true;
            } else {
                $result[] = $tag;
            }
        }
        if ($replace) {
            $this->tags = $result;
            $this->fixParentOrders();
        }
    }

    /**
     * Checks if a internal tag of a certain type is present
     */
    public function hasType(string $type, bool $includeDeleted = false): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && ($includeDeleted || ! $tag->wasDeleted)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves if there are trackchanges tags present
     */
    public function hasTrackChanges(): bool
    {
        return $this->hasType(editor_Segment_Tag::TYPE_TRACKCHANGES, true);
    }

    /**
     * TermTagging may introduces errors with wrong nesting of tags
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

    /**
     * Checks if a internal tag of a certain type and class is present
     */
    public function hasTypeAndClass(string $type, string $className, bool $includeDeleted = false): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && $tag->hasClass($className) && ($includeDeleted || ! $tag->wasDeleted)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a internal tag of a certain type is present that has at least one of the given classnames
     * @param string[] $classNames
     */
    public function hasTypeAndClasses(string $type, array $classNames, bool $includeDeleted = false): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && $tag->hasClasses($classNames) && ($includeDeleted || ! $tag->wasDeleted)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves if there are tags of the specified class and type that either are between the given boundaries
     * or overlap with the section defined by the given boundaries
     */
    public function hasTypeAndClassBetweenIndices(
        string $type,
        string $className,
        int $fromIdx,
        int $toIdx,
        bool $includeDeleted = false
    ): bool {
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type
                && $tag->hasClass($className)
                && ($includeDeleted || ! $tag->wasDeleted)
                && (($tag->startIndex >= $fromIdx && $tag->startIndex < $toIdx)
                    || ($tag->endIndex >= $fromIdx && $tag->endIndex < $toIdx))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves, how many internal tags representing whitespace, are present
     */
    public function getNumLineBreaks(): int
    {
        $numLineBreaks = 0;
        foreach ($this->tags as $tag) {
            if ($tag->getType() == editor_Segment_Tag::TYPE_INTERNAL && $tag->isNewline()) {
                $numLineBreaks++;
            }
        }

        return $numLineBreaks;
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
        $num = count($this->tags);
        $textLength = $this->getFieldTextLength();
        for ($i = 0; $i < $num; $i++) {
            $tag = $this->tags[$i];
            $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $textLength);
            $tag->content = $this->getTextPart($tag->startIndex, $tag->endIndex);
            $tag->field = $this->field;
            $tag->finalize($this, $this->task);
        }
        // after unserialization, we set the wasDeleted / wasInserted properties of our tags
        $this->evaluateDeletedInserted();
    }

    protected function createFromHtmlNode(HtmlNode $node, int $startIndex, array $children = null): editor_Segment_Tag
    {
        return editor_Segment_TagCreator::instance()->fromHtmlNode($node, $startIndex);
    }

    protected function createFromDomElement(DOMElement $element, int $startIndex, DOMNodeList $children = null): editor_Segment_Tag
    {
        return editor_Segment_TagCreator::instance()->fromDomElement($element, $startIndex);
    }

    /* Cloning API */

    /**
     * Clones the tags with only the types of tags specified
     * Note, that you will not be able to filter trackchanges-tags out, use ::cloneWithoutTrackChanges instead for this
     */
    public function cloneFiltered(array $includedTypes = null, bool $finalize = true): editor_Segment_FieldTags
    {
        $clonedTags = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->text, $this->field, $this->dataField, $this->saveTo, $this->ttName);
        foreach ($this->tags as $tag) {
            if ($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES || ($includedTypes == null || in_array($tag->getType(), $includedTypes))) {
                $clonedTags->addTag($tag->clone(true, true), $tag->order, $tag->parentOrder);
            }
        }
        if ($finalize) {
            $clonedTags->fixParentOrders();
        }

        return $clonedTags;
    }

    /**
     * Clones without trackchanges tags. Deleted contents (in del-tags) will be removed and all text-lengths/indices will be adjusted
     */
    public function cloneWithoutTrackChanges(array $includedTypes = null, bool $condenseBlanks = true): editor_Segment_FieldTags
    {
        $clonedTags = $this->cloneFiltered($includedTypes, false);
        if (! $clonedTags->deleteTrackChangesTags($condenseBlanks)) {
            $clonedTags->fixParentOrders();
        }

        return $clonedTags;
    }

    /**
     * Replaces the tag at the given index to a placehandler.
     * Be aware, that this can only be done with singular tags currently and otherwise leads to an exception
     * @throws ZfExtended_Exception
     */
    public function toPlaceholderAt(int $index, string $placeholder): editor_Segment_PlaceholderTag
    {
        if ($index < count($this->tags)) {
            $tag = $this->tags[$index];
            if ($tag->isSingular()) {
                $this->tags[$index] = new editor_Segment_PlaceholderTag($tag->startIndex, $tag->endIndex, $placeholder);

                return $this->tags[$index];
            }

            throw new ZfExtended_Exception('Only singular Segment-tags can currently be turned to placeholder-tags');
        }

        throw new ZfExtended_Exception('toPlaceholderAt: Index out of boundaries');
    }

    /**
     * Removes all TrackChanges tags, also deletes all contents of del-tags
     */
    private function deleteTrackChangesTags(bool $condenseBlanks = true): bool
    {
        $this->evaluateDeletedInserted(); // ensure this is properly set (normally always the case)
        $this->sort(); // making sure we're in order
        $hasTrackChanges = false;
        foreach ($this->tags as $tag) {
            if ($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES) {
                $tag->wasDeleted = true;
                if ($tag->isDeleteTag() && $tag->endIndex > $tag->startIndex) {
                    if ($condenseBlanks) {
                        $boundries = $this->getRemovableBlanksBoundries($tag->startIndex, $tag->endIndex);
                        if ($boundries->left < $tag->startIndex && $boundries->right > $tag->endIndex) {
                            // if there are removable blanks on both sides it is meaningless, on which side we leave one
                            $tag->startIndex = $boundries->left;
                            $tag->endIndex = $boundries->right - 1;
                        }
                    }
                    $this->cutIndicesOut($tag->startIndex, $tag->endIndex);
                }
                $hasTrackChanges = true;
            }
        }
        if ($hasTrackChanges) {
            $newTags = [];
            foreach ($this->tags as $tag) {
                // removes the del-tags the "hole punching" may created more deleted tags - should not happen though
                if (! $tag->wasDeleted) {
                    if ($tag->wasInserted) {
                        $tag->wasInserted = false;
                    }
                    $newTags[] = $tag;
                }
            }
            $this->tags = $newTags;
            $this->fixParentOrders();
            $this->sort();
        }

        return $hasTrackChanges;
    }

    /**
     * Retrieves the boundries of a del-tag increased by the blanks that can be removed without affecting other tags
     */
    private function getRemovableBlanksBoundries(int $start, int $end): stdClass
    {
        $length = $this->getFieldTextLength();
        $boundries = new stdClass();
        $boundries->left = $start;
        $boundries->right = $end;
        // increase the boundries to cover all blanks left and right
        while (($boundries->left - 1) > 0 && $this->getTextPart($boundries->left - 1, $boundries->left) == ' ') {
            $boundries->left -= 1;
        }
        while (($boundries->right + 1) < $length && $this->getTextPart($boundries->right, $boundries->right + 1) == ' ') {
            $boundries->right += 1;
        }
        // reduce the boundries if there are tags covered
        foreach ($this->tags as $tag) {
            if (! $tag->wasDeleted && $tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES) {
                if ($tag->startIndex >= $boundries->left && $tag->startIndex <= $start) {
                    $boundries->left = $tag->startIndex;
                }
                if ($tag->endIndex <= $boundries->right && $tag->endIndex >= $end) {
                    $boundries->right = $tag->endIndex;
                }
            }
        }

        return $boundries;
    }

    /**
     * Removes the text-portion from our field-text and our tags
     */
    private function cutIndicesOut(int $start, int $end): void
    {
        $dist = $end - $start;
        if ($dist <= 0) {
            return;
        }
        // adjust the tags
        foreach ($this->tags as $tag) {
            // the tag is only affected if not completely  before the hole
            if ($tag->endIndex > $start) {
                // if we're completely behind, just shift
                if ($tag->startIndex >= $end) {
                    $tag->startIndex -= $dist;
                    $tag->endIndex -= $dist;
                } elseif ($tag->startIndex >= $start && $tag->endIndex <= $end) {
                    // singular boundry tags will only be shifted
                    if ($tag->endIndex == $start || $tag->startIndex == $end) {
                        $tag->startIndex -= $dist;
                        $tag->endIndex -= $dist;
                    } else {
                        // this can only happen, if non-trackchanges tags overlap with trackchanges tags. TODO: generate an error here ?
                        if ($tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES && ! $tag->wasDeleted && static::DO_DEBUG) {
                            error_log("\n##### TRACKCHANGES CLONING: FOUND TAG THAT HAS TO BE REMOVED ALTHOUGH NOT MARKED AS DELETED ($start|$end) " . $tag->debugProps() . " #####\n");
                        }
                        $tag->startIndex = $tag->endIndex = 0;
                        $tag->wasDeleted = true;
                    }
                } else {
                    // tag is somehow overlapping the hole
                    $tag->startIndex = ($tag->startIndex <= $start) ? $tag->startIndex : $start;
                    $tag->endIndex = ($tag->endIndex >= $end) ? ($tag->endIndex - $dist) : ($end - $dist);
                }
            }
        }
        // adjust the field text
        $length = $this->getFieldTextLength();
        $newFieldText = ($start > 0) ? $this->getTextPart(0, $start) : '';
        $newFieldText .= ($end < $length) ? $this->getTextPart($end, $length) : '';
        $this->setText($newFieldText);
    }

    /**
     * Retrieves the text with the TrackChanges removed
     */
    private function getFieldTextWithoutTrackChanges(bool $condenseBlanks = true): string
    {
        $this->sort();
        $text = '';
        $start = 0;
        $length = $this->getFieldTextLength();
        foreach ($this->tags as $tag) {
            // the tag is only affected if not completely  before the hole
            if ($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES && $tag->isDeleteTag() && $tag->endIndex > $tag->startIndex && $tag->endIndex > $start) {
                $boundries = ($condenseBlanks) ? $this->getRemovableBlanksBoundries($tag->startIndex, $tag->endIndex) : null;
                if ($boundries != null && $boundries->left < $tag->startIndex && $boundries->right > $tag->endIndex) {
                    // if there are removable blanks on both sides it is meaningless, on which side we leave one
                    if ($boundries->left > $start) {
                        $text .= $this->getTextPart($start, $boundries->left);
                    }
                    $start = $boundries->right - 1;
                } else {
                    if ($tag->startIndex > $start) {
                        $text .= $this->getTextPart($start, $tag->startIndex);
                    }
                    $start = $tag->endIndex;
                }
            }
        }
        if ($start < $length) {
            $text .= $this->getTextPart($start, $length);
        }

        return $text;
    }

    /**
     * Special API to render all internal newline tags as lines
     * This expects TrackChanges Tags to be removed, otherwise the result will contain trackchanges contents
     */
    private function replaceTagsForLines(): void
    {
        $tags = [];
        foreach ($this->tags as $tag) {
            // the tag is only affected if not completely  before the hole
            if ($tag->getType() === editor_Segment_Tag::TYPE_INTERNAL && $tag->isNewline()) {
                $tags[] = editor_Segment_NewlineTag::createNew($tag->startIndex, $tag->endIndex);
            }
        }
        $this->tags = $tags;
    }

    /**
     * Sets the deleted / inserted properties for all tags.
     * This is the last step of unparsing the tags and deserialization from JSON
     * It is also crucial for evaluating qualities because only non-deleted tags will count
     */
    private function evaluateDeletedInserted(): void
    {
        foreach ($this->tags as $tag) {
            if ($tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES) {
                $propName = ($tag->isDeleteTag()) ? 'wasDeleted' : 'wasInserted';
                $this->setContainedTagsProp($tag->startIndex, $tag->endIndex, $tag->order, $propName);
            }
        }
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

    /**
     * Debug formatted JSON
     */
    public function debugJson(): string|false
    {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
