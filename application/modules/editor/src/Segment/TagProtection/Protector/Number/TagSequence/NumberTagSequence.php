<?php

namespace MittagQI\Translate5\Segment\TagProtection\Protector\Number\TagSequence;

use DOMElement;
use DOMNodeList;
use editor_Segment_Tag;
use editor_Segment_TagCreator;
use editor_TagSequence;
use PHPHtmlParser\Dom\Node\HtmlNode;

class NumberTagSequence extends editor_TagSequence
{
    public function __construct(string $markup)
    {
        $this->_setMarkup($markup);
    }

    public function getText() : string
    {
        return $this->text;
    }

    protected function createFromHtmlNode(
        HtmlNode $node,
        int $startIndex,
        array $children = null
    ): editor_Segment_Tag {
        return editor_Segment_TagCreator::instance()->fromHtmlNode($node, $startIndex);
    }

    protected function createFromDomElement(
        DOMElement $element,
        int $startIndex,
        DOMNodeList $children = null
    ): editor_Segment_Tag {
        return editor_Segment_TagCreator::instance()->fromDomElement($element, $startIndex);
    }
}