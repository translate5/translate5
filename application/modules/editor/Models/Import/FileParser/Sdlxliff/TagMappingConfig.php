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

class editor_Models_Import_FileParser_Sdlxliff_TagMappingConfig
{
    public const TAG_MRK_SINGLE = 'mrkSingle';

    public const TAG_MRK_PAIRED = 'mrkPaired';

    /**
     * @var array maps all tag references in the header of the sdlxliff file within
     *      <tag-defs><tag></tag></tag-defs> to the tags in segments of the sdlxliff
     *      that reference them. The reference is always the first child of tag
     *      according to the sdlxliff logic.
     *      Also used to check whether tag names are used in the segments or in the header
     *      that are not considered by this sdlxliff file parser.
     */
    public const TAG_DEF_MAPPING = [
        'bpt' => 'g',
        'ph' => 'x',
        'st' => 'x',
        'mrk' => 'mrk',
        'pairedTag' => 'pairedTag',
    ];

    /**
     * defines the GUI representation of internal used tags
     * Mapping von tagId zu Name und anzuzeigendem Text fuer den Nutzer
     *
     *  - kann in der Klassenvar-Def. bereits Inhalte enthalten, die für spezielle
     *    Zwecke benötigt werden und nicht dynamisch aus der sdlxliff-Datei kommen.
     *
     *    Beispiel bpt:
     *    [1192]=>
     *     array(6) {
     *       ["name"]=>
     *       string(3) "bpt"
     *       ["text"]=>
     *       string(44) "&lt;cf style=&quot;z_AS_disclaimer&quot;&gt;"
     *       ["eptName"]=>
     *       string(3) "ept"
     *       ["eptText"]=>
     *       string(11) "&lt;/cf&gt;"
     *       ["imgEptText"]=>
     *       string(5) "</cf>"
     *     }
     *    Beispiel ph:
     *     [0]=>
     *      array(3) {
     *        ["name"]=>
     *        string(2) "ph"
     *        ["text"]=>
     *        string(58) "&lt;format type=&quot;&amp;lt;fullPara/&amp;gt;&quot;/&gt;"
     *      }
     *
     * @var array array('tagId' => array('text' => string '',['eptName' => string '', 'eptText' => string
     *      '','imgEptText' => string '']),'tagId2' => ...)
     * /
     */
    public const DEFAULT_TAG_MAPPING = [
        self::TAG_MRK_SINGLE => [
            'text' => '&lt;InternalReference/&gt;',
        ],
        self::TAG_MRK_PAIRED => [
            'text' => '&lt;InternalReference&gt;',
            'eptName' => 'ept',
            'eptText' => '&lt;/InternalReference&gt;',
            'imgEptText' => '</InternalReference>',
        ],
    ];
}
