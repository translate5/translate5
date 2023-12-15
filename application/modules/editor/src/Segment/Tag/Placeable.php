<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Segment\Tag;

use DOMDocument;
use DOMNode;
use DOMXPath;
use editor_Models_Import_FileParser_Tag as FileParserTag;
use Throwable;

/**
 *
 */
final class Placeable
{
    const MARKER_CLASS = 't5placeable';

    const DATA_NAME = 't5plcnt';

    const DOCTYPE = '<?xml version="1.0" encoding="UTF-8"?>';

    const ALLOWED_TAGS = ['<b>', '<i>', '<u>', '<strong>', '<sup>', '<sub>'];

    /**
     * Detects Placeables in the given internal-tag markup for the given xpathes
     * @param string $markup
     * @param array $xpathes
     * @param FileParserTag $tag
     * @return void
     */
    public static function detect(string $markup, array $xpathes, FileParserTag $tag): void
    {
        $content = self::searchXpath($markup, $xpathes);
        if($content !== null){
            $tag->placeable = new Placeable($content);
        }
    }

    /**
     * Searches for the passed xpathes in the passed markup and returns the first match
     * @param string $content
     * @param array $xpathes
     * @return string|null
     */
    private static function searchXpath(string $content, array $xpathes): ?string
    {
        try {
            // generate DOM documentt with proper XML doctype avoiding UTF-8 quirks
            $doc = new DOMDocument();
            $doc->loadXML(self::DOCTYPE . self::convertContent($content));
            // create a XPath to query with
            $domXpath = new DOMXpath($doc);

            foreach($xpathes as $xpath){

                $nodes = $domXpath->query(self::convertXpath($xpath));
                if($nodes !== false && $nodes->count() > 0){

                    // Ease-of-Use: When users target a node and not it's children, they usually want the contents !
                    // this is a debatable enhancement
                    if($nodes->count() === 1
                        && !str_ends_with($xpath, 'node()')
                        && !str_ends_with($xpath, 'text()')
                        && !str_ends_with($xpath, '::*')
                        && $nodes[0]->childNodes->count() > 0){
                        $nodes = $nodes[0]->childNodes;
                    }

                    $content = '';
                    foreach($nodes as $node){
                        $content .= self::renderDomNode($node);
                    }
                    return $content;
                }
            }

        } catch(Throwable $e){
            error_log('Detection of Placeables failed: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Retrieves the XML-value of a DOM-node
     * @param DOMNode $node
     * @return string
     */
    private static function renderDomNode(DOMNode $node): string
    {
        if($node->nodeType === XML_ELEMENT_NODE){
            return $node->ownerDocument->saveXML($node);
        }
        if($node->nodeType === XML_TEXT_NODE){
            return $node->textContent;
        }
        return '';
    }

    /**
     * @param string $xpath
     * @return string
     */
    private static function convertXpath(string $xpath): string
    {
        return preg_replace('~([a-zA-Z_]+):([a-zA-Z0-9_.\-]+)~', '\1-\2', $xpath);
    }

    /**
     * @param string $content
     * @return string
     */
    private static function convertContent(string $content): string
    {
        // un-namespace starting & singular tags
        $content = preg_replace('~<([a-zA-Z_]+):([a-zA-Z0-9_.\-]+)~', '<\1-\2', $content);
        // un-namespace ending tags
        $content = preg_replace('~</([a-zA-Z_]+):([a-zA-Z0-9_.\-]+)~', '</\1-\2', $content);
        // un-namespace attribute names
        return preg_replace('~ ([a-zA-Z_]+):([a-zA-Z0-9_.\-]+)\s*=~', ' \1-\2=', $content);
    }

    private string $content;

    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $this->clean($content);
    }

    /**
     * Strips all tags albeit the allowed ones for placeables and removes all attributes from the allowed
     * @param string $markup
     * @return array|string|string[]|null
     */
    public function clean(string $markup)
    {
        if(strip_tags($markup) !== $markup){
            $markup = strip_tags($markup, self::ALLOWED_TAGS);
            $markup = preg_replace('/<([a-z][a-z0-9]*)[^<|>]*?(\/?)>/si','<\1\2>', $markup);
        }
        return $markup;
    }

    /**
     * Retrieves the content of a placeable
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Retrieves the content-length of a placeable
     * @return int
     */
    public function getContentLength(): int
    {
        return strlen(strip_tags($this->content));
    }

    /**
     * Retrieves the CSS-class to identify a placeable
     * @return string
     */
    public function getCssClass(): string
    {
        return self::MARKER_CLASS;
    }

    /**
     * Retrieves the data-attribute for a Placeable
     * @return string
     */
    public function getDataAttribute(): string
    {
        $content = htmlspecialchars($this->content, ENT_XML1 | ENT_COMPAT, 'UTF-8', false);
        return 'data-' . self::DATA_NAME . '="' . $content . '"';
    }
}
