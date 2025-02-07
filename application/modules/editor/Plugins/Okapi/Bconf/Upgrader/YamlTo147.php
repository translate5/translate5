<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Upgrader;

/**
 * Class representing Yaml updates to v1.47
 */
final class YamlTo147
{
    private const replaceFprmYamlData = [
        'okf_html' => [
            "[keywords, description]" => "[keywords, description, 'twitter:title', 'twitter:description', 'og:title', 'og:description', 'og:site_name']",
            ".*:
    ruleTypes: [EXCLUDE]
    conditions: [translate, EQUALS, 'no']" => ".*:
    ruleTypes: [EXCLUDE]
    conditions: [translate, EQUALS, no]
  .+:
    ruleTypes: [INCLUDE]
    conditions: [translate, EQUALS, yes]",
        ],
        'okf_xmlstream' => [
            // "xmlstream-dita"
            ".*:
    ruleTypes: [EXCLUDE]
    conditions: [translate, EQUALS, 'no']" => ".*:
    ruleTypes: [EXCLUDE]
    conditions: [translate, EQUALS, no]
  .+:
    ruleTypes: [INCLUDE]
    conditions: [translate, EQUALS, yes]
  msgblock:
    ruleTypes: [PRESERVE_WHITESPACE]
  codeblock:
    ruleTypes: [PRESERVE_WHITESPACE]",
            "state:
    ruleTypes: [INLINE]" => "state:
    ruleTypes: [ATTRIBUTES_ONLY, INLINE]
    translatableAttributes: [value]",
        ],
    ];

    public static function isSupported(string $okfType): bool
    {
        return ! empty(self::replaceFprmYamlData[$okfType]);
    }

    public static function upgrade(string $okfType, string $contents): string
    {
        if (self::isSupported($okfType)) {
            foreach (self::replaceFprmYamlData[$okfType] as $str1 => $str2) {
                $contents = str_replace($str1, $str2, $contents);
            }
        }

        return $contents;
    }
}
