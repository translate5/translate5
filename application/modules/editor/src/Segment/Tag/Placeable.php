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

use editor_Models_Import_FileParser_Tag as FileParserTag;
use SimpleXMLElement;
use Throwable;

/**
 *
 */
final class Placeable
{
    const MARKER_CLASS = 't5placeable';

    const DATA_NAME = 't5plcnt';

    /**
     * Detects Placeables in the given internal-tag markup for the given xpathes
     * @param string $markup
     * @param FileParserTag $tag
     * @param array $xpathes
     * @return void
     */
    public static function detect(string $markup, FileParserTag $tag, array $xpathes): void
    {
        $content = self::searchXpath($markup, $xpathes);
        if($content !== null){
            $tag->placeable = new Placeable($content);
        }
    }

    /**
     * @param string $content
     * @param array $xpathes
     * @return string|null
     */
    private static function searchXpath(string $content, array $xpathes): ?string
    {
        // we wrap into a try-catch, may the content represents funny stuff - which should not hinder import
        try {

            $xml = simplexml_load_string(self::convertContent($content)); // un-namespace namespaces
            foreach($xpathes as $xpath){
                $findings = $xml->xpath(self::convertXpath($xpath));
                if(is_array($findings) && count($findings) > 0){
                    /* @var SimpleXMLElement $found */
                    $found = $findings[0];
                    return $found->asXML();
                }
            }

        } catch(Throwable $e){
            error_log('Detection of Placeables failed: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * @param string $content
     * @return string
     */
    private static function convertXpath(string $xpath): string
    {
        return str_replace(':', '_', $xpath);
    }

    /**
     * @param string $content
     * @return string
     */
    private static function convertContent(string $content): string
    {
        // un-namespace starting & singular tags
        $content= preg_replace('~<([a-zA-Z_]+):([a-zA-Z0-9_.\-]+)~', '<\1_\2', $content);
        // un-namespace ending tags
        $content= preg_replace('~</([a-zA-Z_]+):([a-zA-Z0-9_.\-]+)~', '</\1_\2', $content);
        // un-namespace attribute names
        $content= preg_replace('~ ([a-zA-Z_]+):([a-zA-Z0-9_]+)\s*=', ' \1_\2=', $content);

        return $content;
    }

    /**
     * @param string $content
     */
    public function __construct(private string $content)
    {
    }

    /**
     * @return string
     */
    public function getCssClass(): string
    {
        return self::MARKER_CLASS;
    }

    /**
     * @return string
     */
    public function getDataAttribute(): string
    {
        // TODO PLACEABLES: remove markup!
        $content = htmlspecialchars($this->content, ENT_XML1 | ENT_COMPAT, 'UTF-8', false);
        return 'data-' . self::DATA_NAME . '"' . $content . '"';
    }
}
