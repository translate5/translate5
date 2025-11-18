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

use MittagQI\Translate5\Tag\TagSequence;

/**
 * Abstraction for an Internal tag as used in the segment text's
 * This adds serialization/unserialization-capabilities (JSON), cloning capabilities and the general managability of internal tags
 * Generally internal tags must be used with start & end indice relative to the segment texts.
 * Keep in mind that start & end-index work just like counting chars or the substr API in php, the tag starts BEFORE the start index and ends BEFORE the index of the end index, if you want to cover the whole segment the indices are 0 and mb_strlen($segment)
 * To identify the Types of Internal tags a general API editor_Segment_TagCreator is provided
 * @property editor_Segment_Tag[] $children
 * @phpstan-consistent-constructor
 */
class editor_Segment_Tag extends editor_Tag implements JsonSerializable
{
    /**
     * @var string
     */
    public const TYPE_TRACKCHANGES = 'trackchanges';

    /**
     * @var string
     */
    public const TYPE_INTERNAL = 'internal';

    /**
     * @var string
     */
    public const TYPE_MQM = 'mqm';

    /**
     * @var string
     */
    public const TYPE_QM = 'qm';

    /**
     * A special Type only used for tests and as a default for unknown tags in segment content
     * @var string
     */
    public const TYPE_ANY = 'any';

    /**
     * @var string
     */
    public const CSS_CLASS_TOOLTIP = 'ownttip';

    /**
     * @var string
     */
    public const CSS_CLASS_FALSEPOSITIVE = 't5qfalpos';

    /**
     * @var string
     */
    public const DATA_NAME_QUALITYID = 't5qid';

    private const DATA_QUALITY_ID_PREFIX = 'ext-';

    /**
     * The counterpart to ::toJson: creates the tag from the serialized json data
     * @throws Exception
     */
    public static function fromJson(string $jsonString): editor_Segment_Tag
    {
        try {
            $data = json_decode($jsonString);

            return editor_Segment_TagCreator::instance()->fromJsonData($data);
        } catch (Throwable) {
            throw new Exception('Could not deserialize editor_Segment_Tag from JSON String ' . $jsonString);
        }
    }

    /**
     * Evaluates if the passed type matches our type
     */
    public static function isType(string $type): bool
    {
        return ($type == static::$type);
    }

    /**
     * Evaluates if the passed nodename matches our nodename
     */
    public static function hasNodeName(string $name): bool
    {
        return ($name == static::$nodeName);
    }

    /**
     * Creates a new instance of the Tag.
     * For Segment-tags always use ::createNew for creating a fresh instance with the neccessary props set (identification-class, nade-name), never use ::create()
     */
    public static function createNew(int $startIndex = 0, int $endIndex = 0, string $category = ''): static
    {
        $tag = new static($startIndex, $endIndex, $category);
        $tag->addClass($tag->getIdentificationClass());

        return $tag;
    }

    /**
     * Strips all segment tags from a string
     */
    public static function strip(string $markup): string
    {
        $markup = preg_replace(editor_Segment_Internal_Tag::REGEX_REMOVE, '', $markup);

        return strip_tags($markup);
    }

    /**
     * @deprecated: do not use with segment tags
     * @throws Exception
     */
    public static function create(string $tagName): editor_Tag
    {
        throw new Exception('Direct instantiation via ::create is not appropriate, use ::createNew instead');
    }

    /**
     * The type of Internal tag (e.g, QC for Quality Control), to be set via inheritance
     */
    protected static ?string $type = null;

    /**
     * The node name for the internal tag. This is set as a static property here instead of setting it dynamicly as in editor_Tag
     */
    protected static ?string $nodeName = null;

    /**
     * Defines the identifying class name for the tag, that all tags of this type must have
     */
    protected static ?string $identificationClass = null;

    /**
     * For compatibility with old code/concepts some quality tags may not use the global date-attribute for the quality ID
     */
    protected static ?string $historicDataNameQid = null;

    /**
     * The start character Index of the Tag in relation to the segment's text
     * The opening tag will be rendered BEFORE this char
     */
    public int $startIndex = 0;

    /**
     * The end character Index of the Tag in relation to the segment's text.
     * The closing tag will be rendered BEFORE this char
     */
    public int $endIndex = 0;

    /**
     * This saves the order of the tag as found on creation.
     * This is e.g. crucial for multiple singular tags beside each other or singular tags on the start or end
     * of the covered markup that are directly beside tags with content
     */
    public int $order = -1;

    /**
     * This saves the order of the parent tag as found on creation (if there is a parent tag).
     * This is e.g. crucial for singular tags contained in other tags or
     * to specify the nesting if tags have identical start & end indices
     */
    public int $parentOrder = -1;

    /**
     * Holds the order of a closer of a paired tag in the phase of serialization
     * Otherwise meanigless
     */
    public int $rightOrder = -1;

    /**
     * Set by TagSequence to indicate that the Tag spans the complete segment text
     */
    public bool $isFullLength = false;

    /**
     * Set by TagSequence to indicate that the Tag was deleted at some time in the segment's history (is in a del-tag)
     */
    public bool $wasDeleted = false;

    /**
     * Set by TagSequence to indicate that the Tag was inserted at some time in the segment's history (is in an ins-tag)
     */
    public bool $wasInserted = false;

    /**
     * References the field the Tag belongs to
     * This property is only set, if the tag is part of a FieldTags container and will not be serialized !
     */
    public ?string $field = null;

    /**
     * References the text content of the tag.
     * This property is only set, if the tag is part of a FieldTags container and will not be serialized !
     * This prop NEVER can be used to change segments in the DB or the like, it has only informative character and is used for internal comparisions
     */
    public string $content = '';

    /**
     * Only needed in the rendering process
     */
    public array $cuts = [];

    /**
     * Only needed in the consolidation phase of the unparsing
     */
    public bool $removed = false;

    /**
     * The category of tag we have, a further specification of type
     * might not be used by all internal tags
     */
    protected string $category = '';

    /**
     * The Constructor parameters must not be changed in extending classes, otherwise the ::fromJson API will fail !
     * @throws Exception
     */
    public function __construct(int $startIndex, int $endIndex, string $category = '')
    {
        if (static::$type === null || static::$nodeName === null) {
            throw new Exception('Direct instantiation of editor_Segment_Tag is not appropriate, type and nodeName must not be NULL');
        }
        parent::__construct(static::$nodeName);
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->category = $category;
    }

    /**
     * Overwritten API to take the skipped types parameter into account
     * {@inheritDoc}
     * @see editor_Tag::render()
     */
    public function render(array $skippedTypes = null): string
    {
        if ($skippedTypes != null && in_array($this->getType(), $skippedTypes)) {
            return $this->renderChildren($skippedTypes);
        }

        return $this->renderStart() . $this->renderChildren($skippedTypes) . $this->renderEnd();
    }

    public function toJson(): false|string
    {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function createBaseClone(): static
    {
        return new static($this->startIndex, $this->endIndex, $this->category);
    }

    /**
     * Opposing to the base-class the node-name with internal text is fixed with the type usually (not with editor_Segment_AnyTag ...)
     * {@inheritDoc}
     * @see editor_Tag::getName()
     */
    public function getName(): string
    {
        if (static::$nodeName !== null) {
            return static::$nodeName;
        }

        return parent::getName();
    }

    /**
     * Retrieves the type of internal tag
     */
    public function getType(): string
    {
        return static::$type;
    }

    /**
     * Retrieves, if we are of the passed type
     */
    public function isOfType(string $type): bool
    {
        return (static::$type === $type);
    }

    /**
     * Retrieves the category
     * NOTE: any connection between classes and categories must be coded in the inheriting class
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * All tags of this type must have this class, but note, that this may not be a undoubtable way to identify tags of this type
     */
    public function getIdentificationClass(): string
    {
        return static::$identificationClass;
    }

    /**
     * Sets the category
     * NOTE: any connection between classes and categories must be coded in the inheriting class
     */
    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Retrieves if the tag is of a category that is relevant for quality management
     * @return boolean
     */
    public function hasCategory(): bool
    {
        return ! empty($this->category);
    }

    /**
     * Retrieves the quality ID. If not encoded in the tag, returns -1
     */
    public function getQualityId(): int
    {
        // Compatibility with old code: if there is an existing entry we simply turn it into the current format
        if (static::$historicDataNameQid !== null && $this->hasData(static::$historicDataNameQid)) {
            // transfer only if not present
            if (! array_key_exists('data-' . static::DATA_NAME_QUALITYID, $this->attribs)) {
                $this->attribs['data-' . static::DATA_NAME_QUALITYID] =
                    $this->attribs['data-' . static::$historicDataNameQid];
            }

            unset($this->attribs['data-' . static::$historicDataNameQid]);
        }

        if (! $this->hasData(static::DATA_NAME_QUALITYID)) {
            return -1;
        }

        $qualityId = str_replace(
            self::DATA_QUALITY_ID_PREFIX,
            '',
            $this->getData(static::DATA_NAME_QUALITYID)
        );

        return is_numeric($qualityId) ? (int) $qualityId : -1;
    }

    public function setQualityId(int $qualityId): static
    {
        $this->setData(static::DATA_NAME_QUALITYID, (string) $qualityId);

        return $this;
    }

    /**
     * Retrieves additional data to identify a tag from quality entries
     * This data is stored in the quality entries
     * This API can only be used after tags are part of a FieldTags container / unparsed from a segment
     * This normally covers just a hash of the tags content, to add more data extend this method
     */
    public function getAdditionalData(): stdClass
    {
        $data = new stdClass();
        $data->content = $this->content;
        $data->hash = md5($this->content);

        return $data;
    }

    /**
     * Retrieves if two tags are completely on the same text-index
     * @param editor_Segment_Internal_Tag $tag
     */
    public function hasSameTextIndex(editor_Segment_Tag $tag): bool
    {
        return $this->startIndex === $this->endIndex
            && $tag->startIndex === $tag->endIndex
            && $this->startIndex === $tag->startIndex;
    }

    /**
     * Retrieves if a tag has no text-length, so it's start-index equals it's end-index
     */
    public function hasZeroLength(): bool
    {
        return $this->startIndex === $this->endIndex;
    }

    /**
     * Identifies a tag by a quality entry from the DB
     * This is needed only for the persistance of the falsePositive flag, all other props will be re-evaluated anyway
     * NOTE: this default implementation checks for the position in the segment OR the content of the tag. Note, that this implementation hypothetically can produce false results when qualities with the same content exist multiple times in the segment
     */
    public function isQualityEqual(editor_Models_Db_SegmentQualityRow $quality): bool
    {
        return ($this->isQualityGenerallyEqual($quality) && $this->isQualityContentEqual($quality));
    }

    /**
     * Checks if type & category are equal between us and a quality entry from DB
     */
    protected function isQualityGenerallyEqual(editor_Models_Db_SegmentQualityRow $quality): bool
    {
        return ($this->getType() === $quality->type && $this->getCategory() == $quality->category);
    }

    /**
     * Checks, if the quality content is equal. The base implementation compares the position in the segment or the content/text in the tag (comparing the tag content-hashes)
     * This default implementation is not very specific (using text-position) and may leads to confusion of tags of the same type & category
     */
    protected function isQualityContentEqual(editor_Models_Db_SegmentQualityRow $quality): bool
    {
        return (($this->startIndex === $quality->startIndex && $this->endIndex === $quality->endIndex) || $quality->isAdditionalDataEqual($this->getAdditionalData()));
    }

    /**
     * Retrieves if the tag has the false-positive decorator
     */
    public function isFalsePositive(): bool
    {
        return $this->hasClass(static::CSS_CLASS_FALSEPOSITIVE);
    }

    /**
     * Retrieves the false-positiveness as DB val / int
     */
    public function getFalsePositiveVal(): int
    {
        if ($this->isFalsePositive()) {
            return 1;
        }

        return 0;
    }

    public function setFalsePositive(int $falsePositive = 1): static
    {
        if ($falsePositive == 1) {
            $this->addClass(static::CSS_CLASS_FALSEPOSITIVE);
        } else {
            $this->removeClass(static::CSS_CLASS_FALSEPOSITIVE);
        }

        return $this;
    }

    public function jsonSerialize(): stdClass
    {
        $data = new stdClass();
        $data->type = static::$type;
        $data->name = $this->getName();
        $data->category = $this->getCategory();
        $data->startIndex = $this->startIndex;
        $data->endIndex = $this->endIndex;
        $data->order = $this->order;
        $data->parentOrder = $this->parentOrder;
        $data->classes = $this->classes;
        $data->attribs = editor_Tag::encodeAttributes($this->attribs);
        $this->furtherSerialize($data);

        return $data;
    }

    public function jsonUnserialize(stdClass $data): void
    {
        $this->category = $data->category;
        $this->startIndex = $data->startIndex;
        $this->endIndex = $data->endIndex;
        $this->order = $data->order;
        $this->parentOrder = $data->parentOrder;
        $this->classes = $data->classes;
        $this->attribs = editor_Tag::decodeAttributes($data->attribs);
        $this->furtherUnserialize($data);
    }

    /**
     * Use in inheriting classes for further serialization
     */
    protected function furtherSerialize(stdClass $data): void
    {
    }

    /**
     * Use in inheriting classes for further unserialization
     */
    protected function furtherUnserialize(stdClass $data): void
    {
    }

    /**
     * Additionally to the base function we check also for the same segment tag type
     * {@inheritDoc}
     * @see editor_Tag::isEqual()
     */
    public function isEqual(editor_Tag $tag, bool $withDataAttribs = true): bool
    {
        return ($this->isEqualType($tag) && parent::isEqual($tag, $withDataAttribs));
    }

    /**
     * Determines, if Internal tags are of an equal type
     * {@inheritDoc}
     * @see editor_Tag::isEqualType()
     */
    public function isEqualType(editor_Tag $tag): bool
    {
        if (is_a($tag, 'editor_Segment_Tag') && $tag->getType() == $this->getType()) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves, if the tag can be splitted (to solve overlappings with other tags). This means, that identical tags will be joined when consolidating
     * API is used in the consolidation phase only
     */
    public function isSplitable(): bool
    {
        return true;
    }

    /**
     * In the consolidation phase all obsolete segment tags will be discarded
     * API is used after the consolidation phase only
     */
    public function isObsolete(): bool
    {
        return false;
    }

    /**
     * This method is called when in the end of the consolidation phase obsolete tags and paired-closers (that found no openers) are removed
     * It can be used to log stuff, throw exceptions, etc.
     * API is used after the consolidation phase only
     */
    public function onConsolidationRemoval(): void
    {
    }

    /**
     * Some Internal Tags are IMG-tags that are paired (parted into an opening and closing tag represented by images)
     * This API can be used after the consolidation to identify paired tags
     */
    public function isPaired(): bool
    {
        return false;
    }

    /**
     * Some Internal Tags are IMG-tags that are paired (parted into an opening and closing tag represented by images)
     * These tags will be joined to one tag in the consolidation process.
     * API is used in the consolidation phase only
     */
    public function isPairedOpener(): bool
    {
        return false;
    }

    /**
     * The counterpart to ::isPairedOpener()
     * API is used in the consolidation phase only
     */
    public function isPairedCloser(): bool
    {
        return false;
    }

    /**
     * In the process of joining paired tags this API will be used. The passed tag will be removed when true is returned
     * API is used in the consolidation phase only
     */
    public function pairWith(editor_Segment_Tag $tag): bool
    {
        return false;
    }

    /**
     * Checks, if this segment tag can contain the passed segment tag
     * API is used in the rendering process only
     */
    public function canContain(editor_Segment_Tag $tag): bool
    {
        if (! $this->isSingular()) {
            if ($this->startIndex <= $tag->startIndex && $this->endIndex >= $tag->endIndex) {
                // when tag are aligned with our boundries it is unclear if they are inside or outside,
                // so let's decide by the parentship on creation
                if (($tag->endIndex === $this->startIndex || $tag->startIndex === $this->endIndex)) {
                    return ($tag->parentOrder === $this->order);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Finds the next container that can contain the passed tag
     * API is used in the rendering phase only
     */
    public function getNearestContainer(editor_Segment_Tag $tag): ?editor_Segment_Tag
    {
        if ($this->canContain($tag)) {
            return $this;
        }
        if ($this->parent != null && is_a($this->parent, 'editor_Segment_Tag')) {
            return $this->parent->getNearestContainer($tag);
        }

        return null;
    }

    /**
     * For rendering all tags are cloned and the orders need to be cloned as well
     */
    public function cloneOrder(editor_Segment_Tag $from): void
    {
        $this->order = $from->order;
        $this->parentOrder = $from->parentOrder;
    }

    /**
     * Clones the tag to create the rendering model
     * API is used in the rendering phase only
     */
    public function cloneForRendering(): static
    {
        $clone = $this->clone(true);
        $clone->cloneOrder($this);

        return $clone;
    }

    /**
     * Adds our rendering clone to the rendering queue
     * API is used in the rendering phase only
     */
    public function addRenderingClone(array &$renderingQueue): void
    {
        $renderingQueue[] = $this->cloneForRendering();
    }

    /**
     * After the nested structure of tags is set this fills in the text-chunks of the segments text
     * CRUCIAL: at this point only editor_Segment_Tag must be added as children !
     * API is used in the rendering process only
     */
    public function addSegmentText(TagSequence $tags): void
    {
        if ($this->startIndex < $this->endIndex) {
            if ($this->hasChildren()) {
                // crucial: we need to sort our children as tags with the same start-position but length 0 must come first
                usort($this->children, [TagSequence::class, 'compareChildren']);
                // fill the text-gaps around our children with text-parts of the segments & fill our children with text
                $chldrn = [];
                $last = $this->startIndex;
                foreach ($this->children as $child) {
                    if (is_a($child, editor_Segment_Tag::class)) {
                        /* @var $child editor_Segment_Tag */
                        if ($last < $child->startIndex) {
                            $chldrn[] = editor_Tag::createText($tags->getTextPart($last, $child->startIndex));
                        }
                        $child->addSegmentText($tags);
                        $chldrn[] = $child;
                        $last = $child->endIndex;
                    }
                }
                if ($last < $this->endIndex) {
                    $chldrn[] = editor_Tag::createText($tags->getTextPart($last, $this->endIndex));
                }
                $this->children = $chldrn;
            } else {
                $this->addText($tags->getTextPart($this->startIndex, $this->endIndex));
            }
        }
    }

    /* Unparsing API */

    /**
     * Adds us and all our children to the segment tags
     */
    public function sequence(TagSequence $tags, int $parentOrder): void
    {
        $tags->addTag($this, -1, $parentOrder);
        $this->sequenceChildren($tags, $this->order);
    }

    /**
     * Adds all our children to the segment tags
     */
    public function sequenceChildren(TagSequence $tags, int $parentOrder = -1): void
    {
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                if (is_a($child, editor_Segment_Tag::class)) {
                    /* @var $child editor_Segment_Tag */
                    $child->sequence($tags, $parentOrder);
                }
            }
        }
    }

    /**
     * This API finishes the Field Tags generation. It is the final step and finishing work can be made here, e.g. the evaluation of task specific data
     */
    public function finalize(editor_Segment_FieldTags $tags, editor_Models_Task $task): void
    {
    }

    /* Debugging API */

    /**
     * Debug output
     */
    public function debug(): string
    {
        $newline = "\n";

        return 'RENDERED: ' . trim($this->render()) . $newline .
            'START: ' . $this->startIndex . ' | END: ' . $this->endIndex . ' | FULLENGTH: ' .
                ($this->isFullLength ? 'true' : 'false') . $newline .
            'DELETED: ' . ($this->wasDeleted ? 'true' : 'false') . ' | INSERTED: ' .
                ($this->wasInserted ? 'true' : 'false') . $newline .
            'ORDER: ' . $this->order . ' | PARENT ORDER: ' . $this->parentOrder . $newline;
    }

    public function debugProps(): string
    {
        return $this->debugTag() . ' [ start:' . $this->startIndex . ' | end:' . $this->endIndex . '| order:' .
            $this->order . ' | parentOrder:' . $this->parentOrder . ' | ' .
            ($this->isSplitable() ? 'splitable' : 'not splitable') . ' ]';
    }

    public function debugTag(): string
    {
        return '<' . $this->getType() . '>';
    }
}
