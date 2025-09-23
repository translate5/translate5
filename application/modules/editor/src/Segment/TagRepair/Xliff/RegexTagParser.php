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

declare(strict_types=1);

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

/**
 * Tag parser which will extract the bx,ex and x tags from the text.
 * In case we need different parser, we can just create new one and use it in the tag repairer.
 */
class RegexTagParser implements TagParserInterface
{
    /**
     * Extract tags from text using regular expressions
     *
     * @return array<TagInterface>
     */
    public function extractTags(string $text): array
    {
        $tags = [];
        // TODO: constant
        $pattern = '/<(bx|ex|x)\s+([^>]+)\/>/';

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $fullTag = $match[0][0];
            $position = $match[0][1];

            $tag = XliffTag::fromString($fullTag, $position);
            if ($tag !== null) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
