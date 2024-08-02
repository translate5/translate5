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

/**
 * Represents an Internal tag's content-tags
 * This class is not a segment-tag but a special implementation only for usage "inside" of an internal tag
 * Examples of internal content tags:
 * <span class="short" title="&lt;13/&gt;: No-Break Space (NBSP)">&lt;13/&gt;</span>
 * <span class="full" data-originalid="char" data-length="1">⎵</span>
 */
final class editor_Segment_Internal_ContentTag extends editor_Tag implements JsonSerializable
{
    /**
     * @var string
     */
    public const CSS_CLASS_SHORT = 'short';

    /**
     * @var string
     */
    public const CSS_CLASS_FULL = 'full';

    /**
     * Extracts the tag-index (order of the tag within the original field) from the raw text
     * @var string
     */
    public const PATTERN_INDEX = '~&lt;/*([0-9]+)/*&gt;~';

    public static function fromTag(editor_Tag $tag): editor_Segment_Internal_ContentTag
    {
        $contentTag = new editor_Segment_Internal_ContentTag($tag->getName());
        $tag->transferProps($contentTag, true);
        $contentTag->setInnerHTML($tag->renderChildren());

        return $contentTag;
    }

    /**
     * @throws Exception
     */
    public static function fromJsonData(stdClass $data): editor_Segment_Internal_ContentTag
    {
        try {
            $contentTag = new editor_Segment_Internal_ContentTag($data->name);
            $contentTag->jsonUnserialize($data);

            return $contentTag;
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_Internal_ContentTag from JSON Data ' . json_encode($data));
        }
    }

    private string $innerHTML = '';

    private int $tagIndex = -1;

    /**
     * Evaluates if we are a short internal content tag
     */
    public function isShort(): bool
    {
        return $this->hasClass(self::CSS_CLASS_SHORT);
    }

    /**
     * Evaluates if we are a full internal content tag
     */
    public function isFull(): bool
    {
        return $this->hasClass(self::CSS_CLASS_FULL);
    }

    /**
     * Retrieves the index of the internal tag. Only use on short-tags
     */
    public function getTagIndex(): int
    {
        return $this->tagIndex;
    }

    public function getText(): string
    {
        return strip_tags($this->innerHTML);
    }

    public function getTextLength(): int
    {
        return mb_strlen(strip_tags($this->innerHTML));
    }

    public function addChild(editor_Tag $child): bool
    {
        throw new Exception('Internal Content Tags can not hold children!');
    }

    public function isEmpty(): bool
    {
        return ($this->innerHTML !== null && $this->innerHTML !== '');
    }

    public function renderChildren(array $skippedTypes = null): string
    {
        return $this->innerHTML;
    }

    public function setInnerHTML(string $html): void
    {
        $this->innerHTML = $html;
        if ($this->isShort()) {
            $matches = [];
            if (preg_match(self::PATTERN_INDEX, $this->innerHTML, $matches)) {
                $this->tagIndex = intval($matches[1]);
            }
        }
    }

    public function clone(bool $withDataAttribs = false, bool $withId = false): static
    {
        $tag = parent::clone($withDataAttribs, $withId);
        /* @var $tag editor_Segment_Internal_ContentTag */
        $tag->setInnerHTML($this->innerHTML);

        return $tag;
    }

    protected function createBaseClone(): static
    {
        return new static($this->name);
    }

    public function jsonSerialize(): stdClass
    {
        $data = new stdClass();
        $data->name = $this->getName();
        $data->classes = $this->classes;
        $data->attribs = editor_Tag::encodeAttributes($this->attribs);
        $data->innerHtml = $this->innerHTML;

        return $data;
    }

    public function jsonUnserialize(stdClass $data): void
    {
        $this->classes = $data->classes;
        $this->attribs = editor_Tag::decodeAttributes($data->attribs);
        $this->setInnerHTML($data->innerHtml);
    }
}
