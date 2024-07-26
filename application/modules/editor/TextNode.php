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
 * represents an HTML TextNode as PHP-Object
 * A text node has an empty Node-name and will render only it's text content
 * The Text-Content will not be escaped or unescped within this class so it must already be escaped if escaped content shall be rendered
 *
 * @method editor_TextNode clone(bool $withDataAttribs=false, bool $withId=false)
 * @method editor_TextNode cloneProps(editor_Tag $tag, bool $withDataAttribs=false, bool $withId=false)
 */
final class editor_TextNode extends editor_Tag
{
    public const NODE_NAME = '_TEXT_';

    private string $text = '';

    public function __construct(string $text)
    {
        if (! editor_Tag::isNodeText($text)) {
            throw new Exception('A text-node must have a non-empty text');
        }
        parent::__construct(self::NODE_NAME);
        $this->text = $text;
        $this->name = '';
        $this->singular = true;
    }

    public function isText(): bool
    {
        return true;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getTextLength(): int
    {
        return mb_strlen($this->text);
    }

    public function addChild(editor_Tag $child): bool
    {
        throw new Exception('Text nodes can not hold children!');
    }

    /**
     * In contrast to a "normal" tags addText method this concatenates the given text to our text
     * {@inheritDoc}
     * @see editor_Tag::addText()
     */
    public function addText(string $text): bool
    {
        if (editor_Tag::isNodeText($text)) {
            $this->text .= $text;

            return true;
        }

        return false;
    }

    public function addClass(string $classname): static
    {
        throw new Exception('Text nodes can not have classes');
    }

    public function setAttribute(string $name, ?string $val): static
    {
        throw new Exception('Text nodes can not have attributes');
    }

    public function setData(string $name, ?string $val): static
    {
        throw new Exception('Text nodes can not have data attributes');
    }

    public function addAttribute(string $name, string $val = null): static
    {
        throw new Exception('Text nodes can not have attributes');
    }

    public function isEqual(editor_Tag $tag, bool $withDataAttribs = true): bool
    {
        if (get_class($tag) == 'editor_TextNode' && $tag->getText() == $this->text) {
            return true;
        }

        return false;
    }

    public function isEmpty(): bool
    {
        return ($this->text === '');
    }

    protected function createBaseClone(): static
    {
        return new self($this->text);
    }

    public function render(array $skippedTypes = null): string
    {
        return $this->text;
    }

    public function renderChildren(array $skippedTypes = null): string
    {
        return $this->text;
    }

    /**
     * Text-nodes have to return their text-contents in replaced (stripped) rendering
     */
    public function renderReplaced(string $mode): string
    {
        return $this->text;
    }

    protected function renderStart(bool $withDataAttribs = true): string
    {
        return '';
    }

    protected function renderEnd(): string
    {
        return '';
    }

    /* serialization */

    public function serialize(): stdClass
    {
        $data = new stdClass();
        $data->name = self::NODE_NAME;
        $data->text = $this->text;

        return $data;
    }

    public function unserialize(stdClass $data): static
    {
        $this->text = $data->text;

        return $this;
    }

    public function debugProps(): string
    {
        return '"' . $this->getText() . '"';
    }
}
