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

use MittagQI\Translate5\Segment\Tag\Placeable;

/**
 * Internal struct to contain a tag and its data parts for the import
 * TODO move TagTrait functionality into this class too
 */
class editor_Models_Import_FileParser_Tag
{
    public const RETURN_MODE_INTERNAL = 'internal';

    public const RETURN_MODE_ORIGINAL = 'original';

    public const RETURN_MODE_REMOVED = 'removed';

    public const TYPE_OPEN = 1;

    public const TYPE_CLOSE = 2;

    public const TYPE_SINGLE = 3;

    /**
     * @var array|editor_ImageTag[]
     */
    public static array $renderer = [
        self::TYPE_OPEN => 'editor_ImageTag_Left',
        self::TYPE_CLOSE => 'editor_ImageTag_Right',
        self::TYPE_SINGLE => 'editor_ImageTag_Single',
    ];

    protected int $type;

    /**
     * flag if the tags set as originalContent are XMLish (so starting and ending with < and >)
     */
    protected bool $xmlTags = true;

    /**
     * The originating tag (x|g|it|ph|bx|ex|bpt|ept)
     */
    public string $tag;

    /**
     * TODO change this to private and add accessor and mutator methods
     * @var string mandatory, the id to identity the same tag in source and target
     */
    public string $id;

    /**
     * @var string|null optional, the original id of the tag, if id is owerwritten
     */
    private ?string $originalId = null;

    /**
     * @var string|null optional, the rid to match opening and closing tag, if given
     */
    public ?string $rid = null;

    /**
     * The short tag number used in the GUI
     */
    public mixed $tagNr = null;

    /**
     * The original raw (un-encoded) content contained in the tag
     */
    public string $originalContent;

    /**
     * the text to be rendered as full text content of the tag in the GUI, defaults mostly to the encoded
     * version of originalcontent, but finally depends on the XLF dialect
     */
    public ?string $text = null;

    /**
     * The inner text, stored separately. If it should be used as text: this is calculated on rendering then.
     */
    public ?string $innerTagText = null;

    public bool $useInnerTagText = true;

    /**
     * the rendered tag to be used in the GUI and saved in the DB
     */
    public string $renderedTag;

    public Placeable $placeable;

    /**
     * The partner of a paired tag, null for single tags.
     */
    public ?editor_Models_Import_FileParser_Tag $partner = null;

    protected static string $mode = self::RETURN_MODE_INTERNAL;

    /**
     * sets the mode what tags should return on converting to string, returns the previous mode
     */
    public static function setMode(string $mode = self::RETURN_MODE_INTERNAL): string
    {
        $oldMode = self::$mode;
        self::$mode = $mode;

        return $oldMode;
    }

    /**
     * returns the current tag mode
     */
    public static function getMode(): string
    {
        return self::$mode;
    }

    /**
     * type of tag must be given on construction
     * @param int $type open,close,single see local constants
     * @param bool $xmlTags defines if the given tags as originalContent are XMLish
     *                      (so starting and ending with < and >)
     */
    public function __construct(int $type = self::TYPE_SINGLE, bool $xmlTags = true)
    {
        $this->type = $type;
        $this->xmlTags = $xmlTags;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setSingle(): void
    {
        $this->type = self::TYPE_SINGLE;
    }

    public function isSingle(): bool
    {
        return $this->type === self::TYPE_SINGLE;
    }

    public function isOpen(): bool
    {
        return $this->type === self::TYPE_OPEN;
    }

    public function isClose(): bool
    {
        return $this->type === self::TYPE_CLOSE;
    }

    /**
     * returns the tag content according to the set mode
     */
    public function __toString(): string
    {
        return match (self::$mode) {
            self::RETURN_MODE_REMOVED => '',
            self::RETURN_MODE_ORIGINAL => $this->getOriginalModeContent(),
            default => $this->renderedTag,
        };
    }

    /**
     * returns the content to be used for RETURN_ORIGINAL_MODE rendering
     */
    protected function getOriginalModeContent(): string
    {
        return $this->originalContent;
    }

    /**
     * renders this tag instance as internal tag
     */
    public function renderTag(int $length = -1, string $title = null, string $cls = null): string
    {
        //lazy loading of the image tag renderers
        if (is_string(static::$renderer[$this->type])) {
            static::$renderer[$this->type] = ZfExtended_Factory::get(static::$renderer[$this->type]);
        }

        $classes = [$this->parseSegmentGetStorageClass($this->originalContent, $this->xmlTags)];

        //if a innerTagText is available and single or opener defines to use it, then use it as text.
        // closer tags can not have the useInnerTagText info by nature, so we use the opener to check for it
        if ($this->innerTagText !== null
            && (! $this->isClose() && $this->useInnerTagText
                || $this->isClose() && $this->partner->useInnerTagText)) {
            $this->text = $this->innerTagText;
        }

        $text = $this->text ?? htmlentities($this->originalContent, ENT_COMPAT); //PHP 8.1 fix - default changed!

        if ($cls !== null) {
            $classes[] = trim($cls);
        }
        // special processing of a placeable
        if (isset($this->placeable)) {
            $classes[] = $this->placeable->getCssClass();
            // title will reflect the original tag/content
            $title = htmlspecialchars($text, ENT_COMPAT, null, false);
            // while content/content-length is only the content referenced by the xpath.
            // The Placeable content needs to be escaped for being in an attribute - see text-escaping above
            $text = htmlentities($this->placeable->getContent(), ENT_COMPAT);
            $length = $this->placeable->getContentLength();
        }

        return $this->renderedTag = static::$renderer[$this->type]->getHtmlTag([
            'class' => implode(' ', $classes),
            'text' => $text,
            'shortTag' => $this->tagNr,
            'id' => $this->id, //mostly original tag id
            'length' => $length,
            'title' => $title,
        ]);
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Return the identifier of the tag, which is the tag, the type and the original id (if set)
     * concatenated with a dash
     * Used to identify tag duplicates
     */
    public function getIdentifier(): string
    {
        return sprintf('%s-%s-%s', $this->tag, $this->type, $this->originalId ?? $this->id);
    }

    public function changeId(string $newId): void
    {
        if (null === $this->originalId) {
            $this->originalId = $this->id;
        }

        $this->id = $newId;
    }

    /**
     * helper for parseSegment: encode the tag content without leading and trailing <>
     * checks if $tag starts with < and ends with >
     *
     * @param string $tag contains the tag
     * @param boolean $xmlTags true if the tags are XMLish (so starting and ending with < and >)
     * @return string encoded tag content
     */
    private function parseSegmentGetStorageClass(string $tag, bool $xmlTags): string
    {
        if ($xmlTags) {
            if (! str_starts_with($tag, '<') || ! str_ends_with($tag, '>')) {
                trigger_error('The Tag ' . $tag . ' has not the structure of a tag.', E_USER_ERROR);
            }
            // we store the tag content without leading < and trailing >
            // since we expect to cut of just two ascii characters no mb_ function is needed,
            // the UTF8 content inbetween is untouched
            $tag = substr($tag, 1, -1);
        }

        return implode('', unpack('H*', $tag));
    }
}
