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
use MittagQI\Translate5\Tools\Markup;
use Throwable;

/**
 *
 */
final class Placeable
{
    const MARKER_CLASS = 't5placeable';

    const DETECTION_REGEX = '~<div[^>]*class="[^"]*t5placeable[^"]*internal-tag[^"]*"[^>]*>.+?</div>~i';

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
        $content = self::xpathSearch($markup, $xpathes);
        if($content !== null){
            $tag->placeable = new Placeable($content);
        }
    }

    /**
     * Evaluates, if the given markup contains a placeable
     * @param string $segment
     * @return bool
     */
    public static function contains(string $segment): bool
    {
        return preg_match(self::DETECTION_REGEX, $segment) === 1;
    }

    /**
     * Replaces all Placeables in the given markup with the placeables content
     * @param string $segment
     * @return string
     */
    public static function replace(string $segment): string
    {
        return preg_replace_callback(self::DETECTION_REGEX, function($matches) {
            if(count($matches) === 1){
                $inner = [];
                if(preg_match('~<span[^>]+full[^>]+>(.+)</span>~i', $matches[0], $inner) === 1){
                    if(count($inner) === 2){
                        return strip_tags($inner[1]);
                    }
                }
            }
            return '';
        }, $segment
        ) ?? $segment;
	}

    /**
     * Searches for the passed xpathes in the passed markup and returns the first match
     * @param string $content
     * @param array $xpathes
     * @return string|null
     */
    private static function xpathSearch(string $content, array $xpathes): ?string
    {
        // normal: we search the Placeable in an attribute or in the the content of the xliff
        $match = self::xpathSearchInMarkup($content, $xpathes);
        if($match === null
            && (str_starts_with($content, '<ph') || str_starts_with($content, '<it'))
            && str_ends_with($content, '>')
        ){
            // Special Feature: If the content of a ph/it tag is (escaped) Markup, we search in this markup as well
            // the root for the xpath is the content of the ph/it then
            $content = Markup::unescapeText(strip_tags($content));
            if(Markup::isMarkup($content)){
                $match = self::xpathSearchInMarkup($content, $xpathes);
            }
        }
        return $match;
    }

    /**
     * Searches for the passed xpathes in the passed markup and returns the first match
     * @param string $markup
     * @param array $xpathes
     * @return string|null
     */
    private static function xpathSearchInMarkup(string $markup, array $xpathes): ?string
    {
        try {
            // generate DOM document with proper XML doctype avoiding UTF-8 quirks
            $doc = new DOMDocument();
            $doc->loadXML(self::DOCTYPE . self::convertContent($markup));
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

                    $markup = '';
                    foreach($nodes as $node){
                        $markup .= self::renderDomNode($node);
                    }
                    return $markup;
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
        $content = preg_replace('~<([a-zA-Z_]+):([a-zA-Z0-9_.\-]+)~', '<\1-\2', trim($content));
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
        $markup = htmlspecialchars_decode($markup, ENT_QUOTES);
        $markup = str_replace('&nbsp;', ' ', $markup);
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
}
